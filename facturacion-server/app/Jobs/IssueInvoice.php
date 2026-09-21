<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\ArcaService;
use App\Services\InvoiceRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class IssueInvoice implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;

    public int $tries = 5;

    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public string $invoiceId) {}

    public function handle(ArcaService $arca, InvoiceRenderer $renderer): void
    {
        $invoice = Invoice::with('profile')->findOrFail($this->invoiceId);
        if ($invoice->status === 'completed') {
            return;
        }$invoice->update(['status' => 'processing', 'error' => null]);
        try {
            $authorization = $arca->authorize($invoice->profile, $invoice->request_payload);
            $path = $renderer->render($invoice, $authorization);
            $invoice->update(['status' => 'completed', 'voucher_number' => $authorization['number'], 'cae' => $authorization['cae'], 'cae_expires_at' => $authorization['cae_expires_at'], 'pdf_path' => $path]);
            $recipient = $invoice->request_payload['email_to'] ?? null;
            if ($recipient) {
                $absolute = Storage::path($path);
                Mail::raw('Adjuntamos la factura electrónica de tu reserva.', function ($m) use ($recipient, $absolute, $invoice) {
                    $m->to($recipient)->subject('Factura Refugio Rocca')->bcc(config('billing.bcc'))->attach($absolute, ['as' => 'factura_'.$invoice->external_reference.'.pdf', 'mime' => 'application/pdf']);
                });
                $invoice->update(['emailed_at' => now()]);
            }
        } catch (Throwable $e) {
            $invoice->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 4000)]);
            throw $e;
        }
    }
}
