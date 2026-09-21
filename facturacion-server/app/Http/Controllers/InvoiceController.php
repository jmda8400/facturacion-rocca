<?php

namespace App\Http\Controllers;

use App\Jobs\IssueInvoice;
use App\Models\ArcaProfile;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InvoiceController
{
    public function store(Request $r)
    {
        $d = $r->validate(['external_reference' => 'required|string|max:100', 'profile' => 'required|string|exists:arca_profiles,slug', 'invoice_type' => ['required', Rule::in(['A', 'B'])], 'customer' => 'required|array', 'customer.name' => 'required|string|max:200', 'customer.address' => 'required|string|max:300', 'customer.vat_condition' => 'required|string|max:100', 'customer.document_type' => 'required|integer', 'customer.document_number' => 'required|integer|min:0', 'items' => 'required|array|min:1', 'items.*.description' => 'required|string|max:300', 'items.*.quantity' => 'required|numeric|gt:0', 'items.*.unit_price' => 'required|numeric|min:0', 'total' => 'required|numeric|gt:0', 'email_to' => 'nullable|email:rfc|max:254']);
        $idempotency = (string) $r->header('Idempotency-Key');
        abort_if($idempotency === '' || strlen($idempotency) > 100, 422, 'Debe enviar Idempotency-Key (máximo 100 caracteres).');
        $calculated = round(collect($d['items'])->sum(fn ($i) => (float) $i['quantity'] * (float) $i['unit_price']), 2);
        abort_if(abs($calculated - (float) $d['total']) > .01, 422, 'El total no coincide con los ítems.');
        $existing = Invoice::where('idempotency_key', $idempotency)->first();
        if ($existing) {
            return response()->json($this->resource($existing), 200);
        }$profile = ArcaProfile::where('slug', $d['profile'])->where('active', true)->firstOrFail();
        $invoice = Invoice::create(['idempotency_key' => $idempotency, 'arca_profile_id' => $profile->id, 'external_reference' => $d['external_reference'], 'status' => 'pending', 'invoice_type' => $d['invoice_type'], 'request_payload' => $d]);
        IssueInvoice::dispatch($invoice->id);

        return response()->json($this->resource($invoice), 202);
    }

    public function show(Invoice $invoice)
    {
        return response()->json($this->resource($invoice));
    }

    public function download(Invoice $invoice)
    {
        abort_unless($invoice->status === 'completed' && $invoice->pdf_path && Storage::exists($invoice->pdf_path), 404);

        return Storage::download($invoice->pdf_path, 'factura_'.$invoice->external_reference.'.pdf', ['Content-Type' => 'application/pdf']);
    }

    private function resource(Invoice $i): array
    {
        return ['id' => $i->id, 'external_reference' => $i->external_reference, 'status' => $i->status, 'voucher_number' => $i->voucher_number, 'cae' => $i->cae, 'cae_expires_at' => $i->cae_expires_at?->format('Y-m-d'), 'emailed_at' => $i->emailed_at?->toIso8601String(), 'error' => $i->status === 'failed' ? $i->error : null, 'status_url' => route('api.invoices.show', $i), 'download_url' => $i->status === 'completed' ? route('api.invoices.download',$i) : null];
    }
}
