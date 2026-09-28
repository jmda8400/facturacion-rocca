<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\ArcaService;
use App\Services\BillingEventLogger;
use App\Services\InvoiceRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class IssueInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public string $invoiceId) {}

    public function handle(ArcaService $arca, InvoiceRenderer $renderer, BillingEventLogger $events): void
    {
        $invoice = Invoice::with(['profile', 'billingClient'])->findOrFail($this->invoiceId);
        if ($invoice->status === 'completed') {
            return;
        }

        $invoice->update(['status' => $invoice->cae ? 'authorized' : 'processing', 'error' => null]);
        $events->record('invoice.processing', 'Comenzó o continuó el procesamiento de una factura.', ['invoice_id' => $invoice->id, 'client' => $invoice->billingClient?->slug ?? 'legacy']);

        try {
            if (! $invoice->cae) {
                $lock = Cache::lock("arca-issuance:{$invoice->arca_profile_id}:{$invoice->invoice_type}", 120);
                if (! $lock->get()) {
                    $this->release(30);

                    return;
                }
                try {
                    $result = $this->issueOrReconcile($invoice, $arca, $events);
                    if ($result === null) {
                        return;
                    }
                } finally {
                    $lock->release();
                }
            }

            $invoice->refresh()->load('profile');
            $authorization = $this->storedAuthorization($invoice, $arca);
            if (! $invoice->pdf_path) {
                $path = $renderer->render($invoice, $authorization);
                $invoice->update(['pdf_path' => $path]);
            }

            $invoice->update(['status' => 'completed', 'error' => null]);
            $events->record('invoice.completed', 'Factura autorizada y PDF generado.', ['invoice_id' => $invoice->id, 'voucher_number' => $invoice->voucher_number]);
            if (($invoice->request_payload['email_to'] ?? null) && ! $invoice->emailed_at) {
                try {
                    SendInvoiceEmail::dispatch($invoice->id);
                } catch (Throwable) {
                    // A synchronous queue may surface the mail exception here. The email
                    // job already recorded it; fiscal completion must remain untouched.
                }
            }
        } catch (FiscalConflict $exception) {
            // A conflict is terminal and requires a person; it must never be retried as issuance.
            $invoice->refresh()->update(['status' => 'review_required', 'error' => mb_substr($exception->getMessage(), 0, 4000)]);
            $events->record('invoice.fiscal_conflict', 'El comprobante reservado no coincide con la factura.', ['invoice_id' => $invoice->id, 'voucher_number' => $invoice->voucher_number], 'error');
        } catch (Throwable $exception) {
            $invoice->refresh();
            $invoice->update([
                'status' => $invoice->cae ? 'authorized' : ($invoice->voucher_number ? 'fiscal_pending' : 'failed'),
                'error' => mb_substr($exception->getMessage(), 0, 4000),
            ]);
            $events->record($invoice->cae ? 'invoice.pdf_failed' : 'invoice.fiscal_pending', $invoice->cae ? 'Falló la generación del PDF; la autorización fiscal se conserva.' : 'La emisión quedó pendiente de reconciliación.', ['invoice_id' => $invoice->id, 'voucher_number' => $invoice->voucher_number], 'error');
            throw $exception;
        }
    }

    /** Returns null when this invoice must wait behind an earlier uncertain reservation. */
    private function issueOrReconcile(Invoice $invoice, ArcaService $arca, BillingEventLogger $events): ?array
    {
        $invoice->refresh()->load('profile');
        $type = $arca->voucherType($invoice->invoice_type);

        if ($invoice->voucher_number) {
            $existing = $arca->consult($invoice->profile, $type, (int) $invoice->voucher_number);
            if ($existing) {
                $this->assertMatches($invoice, $existing, $arca);
                $this->persistAuthorization($invoice, $existing);
                $events->record('invoice.reconciled', 'Se recuperó desde ARCA una autorización cuya respuesta se había perdido.', ['invoice_id' => $invoice->id, 'voucher_number' => $invoice->voucher_number]);

                return $existing;
            }
        }

        if ($this->hasEarlierUncertainInvoice($invoice)) {
            $invoice->update(['status' => 'fiscal_pending', 'error' => 'Existe un comprobante anterior reservado pendiente de reconciliación.']);
            $events->record('invoice.sequence_waiting', 'La factura espera la reconciliación de un número anterior.', ['invoice_id' => $invoice->id], 'warning');
            $this->release(60);

            return null;
        }

        $last = $arca->lastAuthorized($invoice->profile, $type);
        if (! $invoice->voucher_number) {
            // This write happens before the network request and is protected both by the
            // distributed lock and the database unique constraint.
            $invoice->update(['voucher_number' => $last + 1, 'status' => 'fiscal_pending']);
            $invoice->refresh();
        }

        $number = (int) $invoice->voucher_number;
        if ($last >= $number) {
            throw new FiscalConflict('ARCA informa que la secuencia ya superó el comprobante reservado, pero FECompConsultar no pudo recuperarlo.');
        }
        if ($last !== $number - 1) {
            $invoice->update(['status' => 'fiscal_pending', 'error' => 'La secuencia ARCA todavía no permite emitir el número reservado.']);
            $this->release(60);

            return null;
        }

        $authorization = $arca->authorize($invoice->profile, $invoice->request_payload, $number);
        $this->assertMatches($invoice, $authorization, $arca);
        $this->persistAuthorization($invoice, $authorization);

        return $authorization;
    }

    private function hasEarlierUncertainInvoice(Invoice $invoice): bool
    {
        return Invoice::query()
            ->where('arca_profile_id', $invoice->arca_profile_id)
            ->where('invoice_type', $invoice->invoice_type)
            ->whereNotNull('voucher_number')
            ->whereNull('cae')
            ->where('id', '!=', $invoice->id)
            ->where(function ($query) use ($invoice) {
                $query->where('created_at', '<', $invoice->created_at)
                    ->orWhere(fn ($sameTime) => $sameTime->where('created_at', $invoice->created_at)->where('id', '<', $invoice->id));
            })
            ->exists();
    }

    private function assertMatches(Invoice $invoice, array $authorization, ArcaService $arca): void
    {
        $expected = $arca->expectedAmounts($invoice->request_payload);
        $customer = $invoice->request_payload['customer'];
        $matches = (int) $authorization['number'] === (int) $invoice->voucher_number
            && (int) $authorization['type'] === $arca->voucherType($invoice->invoice_type)
            && (int) $authorization['sales_point'] === (int) $invoice->profile->sales_point
            && (int) $authorization['document_type'] === (int) $customer['document_type']
            && (int) $authorization['document_number'] === (int) $customer['document_number']
            && (! isset($authorization['concept']) || (int) $authorization['concept'] === (int) $invoice->request_payload['concept'])
            && (! isset($authorization['vat_condition_id']) || (int) $authorization['vat_condition_id'] === (int) $customer['vat_condition_id'])
            && (! isset($authorization['service_from']) || $authorization['service_from'] === ($invoice->request_payload['service_from'] ?? null))
            && (! isset($authorization['service_to']) || $authorization['service_to'] === ($invoice->request_payload['service_to'] ?? null))
            && (! isset($authorization['payment_due_date']) || $authorization['payment_due_date'] === ($invoice->request_payload['payment_due_date'] ?? null))
            && (! isset($authorization['currency']) || $authorization['currency'] === 'PES')
            && (! isset($authorization['currency_rate']) || abs((float) $authorization['currency_rate'] - 1.0) < 0.000001)
            && abs((float) $authorization['total'] - $expected['total']) < 0.01
            && abs((float) $authorization['net'] - $expected['net']) < 0.01
            && abs((float) $authorization['vat'] - $expected['vat']) < 0.01;
        if (! $matches) {
            throw new FiscalConflict('Los datos del comprobante existente en ARCA no coinciden con la factura esperada.');
        }
    }

    private function persistAuthorization(Invoice $invoice, array $authorization): void
    {
        $invoice->update([
            'voucher_date' => $authorization['date'],
            'cae' => $authorization['cae'],
            'cae_expires_at' => $authorization['cae_expires_at'],
            'status' => 'authorized',
            'error' => null,
        ]);
    }

    private function storedAuthorization(Invoice $invoice, ArcaService $arca): array
    {
        $amounts = $arca->expectedAmounts($invoice->request_payload);

        return $amounts + [
            'type' => $arca->voucherType($invoice->invoice_type),
            'sales_point' => (int) $invoice->profile->sales_point,
            'number' => (int) $invoice->voucher_number,
            'date' => ($invoice->voucher_date ?? $invoice->created_at)->format('Y-m-d'),
            'cae' => $invoice->cae,
            'cae_expires_at' => $invoice->cae_expires_at->format('Y-m-d'),
        ];
    }
}

class FiscalConflict extends RuntimeException {}
