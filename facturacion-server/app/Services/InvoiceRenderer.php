<?php

namespace App\Services;

use App\Models\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;

class InvoiceRenderer
{
    public function render(Invoice $invoice, array $arca): string
    {
        $payload = $invoice->request_payload;
        $qr = (new ArcaQrService)->generateDataUri((new ArcaQrService)->buildPayload($arca['date'], $invoice->profile->cuit, $invoice->profile->sales_point, $arca['type'], $arca['number'], $arca['total'], 'PES', 1.0, 'E', $arca['cae'], (int) $payload['customer']['document_type'], (int) $payload['customer']['document_number']));
        $html = view('invoices.pdf', compact('invoice', 'payload', 'arca', 'qr'))->render();
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html);
        $pdf->render();
        $path = 'invoices/'.$invoice->id.'.pdf';
        Storage::put($path, $pdf->output());

        return $path;
    }
}
