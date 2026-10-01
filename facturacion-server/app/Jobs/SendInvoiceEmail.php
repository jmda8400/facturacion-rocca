<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\BillingEventLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SendInvoiceEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public string $invoiceId) {}

    public function handle(BillingEventLogger $events): void
    {
        $invoice = Invoice::findOrFail($this->invoiceId);
        if ($invoice->emailed_at || ! ($recipient = $invoice->request_payload['email_to'] ?? null)) {
            return;
        }

        try {
            $absolute = Storage::path($invoice->pdf_path);
            Mail::raw("Adjuntamos tu factura electrónica.\nPlease find your electronic invoice attached.", function ($message) use ($recipient, $absolute, $invoice) {
                $message->to($recipient)->subject('Factura Refugio Rocca')->bcc(config('billing.bcc'))->attach($absolute, ['as' => 'factura_'.$invoice->external_reference.'.pdf', 'mime' => 'application/pdf']);
            });
            $invoice->update(['emailed_at' => now()]);
            $events->record('invoice.emailed', 'Factura enviada por correo.', ['invoice_id' => $invoice->id]);
        } catch (Throwable $exception) {
            // Email is a post-fiscal side effect: never modify fiscal status or authorization.
            $events->record('invoice.email_failed', 'Falló el envío del correo; se reintentará por separado.', ['invoice_id' => $invoice->id], 'error');
            throw $exception;
        }
    }
}
