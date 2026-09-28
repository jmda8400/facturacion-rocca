<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\ArcaService;
use App\Services\InvoiceRenderer;
use Illuminate\Console\Command;

class RerenderInvoice extends Command
{
    protected $signature = 'billing:invoice:rerender {invoice-id}';

    protected $description = 'Regenera el PDF de una factura ya autorizada sin realizar operaciones en ARCA';

    public function handle(InvoiceRenderer $renderer, ArcaService $arca): int
    {
        $invoice = Invoice::with('profile')->find($this->argument('invoice-id'));

        if (! $invoice) {
            $this->error('No existe la factura indicada.');

            return self::FAILURE;
        }

        if (! $invoice->cae || ! $invoice->voucher_number || ! $invoice->cae_expires_at) {
            $this->error('La factura todavía no posee una autorización fiscal completa.');

            return self::FAILURE;
        }

        $amounts = $arca->expectedAmounts($invoice->request_payload);
        $authorization = $amounts + [
            'type' => $arca->voucherType($invoice->invoice_type),
            'number' => (int) $invoice->voucher_number,
            'date' => ($invoice->voucher_date ?? $invoice->created_at)->format('Y-m-d'),
            'cae' => $invoice->cae,
            'cae_expires_at' => $invoice->cae_expires_at->format('Y-m-d'),
        ];

        $path = $renderer->render($invoice, $authorization);
        $invoice->update(['pdf_path' => $path]);

        $this->info("PDF regenerado: {$path}");

        return self::SUCCESS;
    }
}
