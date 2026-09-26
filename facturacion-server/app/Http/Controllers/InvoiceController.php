<?php

namespace App\Http\Controllers;

use App\Jobs\IssueInvoice;
use App\Models\Invoice;
use App\Services\BillingEventLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InvoiceController
{
    public function store(Request $r, BillingEventLogger $events)
    {
        $d = $r->validate(['external_reference' => 'required|string|max:100', 'profile' => 'nullable|string', 'invoice_type' => ['required', Rule::in(['A', 'B'])], 'customer' => 'required|array', 'customer.name' => 'required|string|max:200', 'customer.address' => 'required|string|max:300', 'customer.vat_condition' => 'required|string|max:100', 'customer.document_type' => 'required|integer', 'customer.document_number' => 'required|integer|min:0', 'items' => 'required|array|min:1', 'items.*.description' => 'required|string|max:300', 'items.*.quantity' => 'required|numeric|gt:0', 'items.*.unit_price' => 'required|numeric|min:0', 'total' => 'required|numeric|gt:0', 'email_to' => 'nullable|email:rfc|max:254']);
        $client = $r->attributes->get('billing_client');
        $idempotency = (string) $r->header('Idempotency-Key');
        abort_if($idempotency === '' || strlen($idempotency) > 100, 422, 'Debe enviar Idempotency-Key (máximo 100 caracteres).');
        $calculated = round(collect($d['items'])->sum(fn ($i) => (float) $i['quantity'] * (float) $i['unit_price']), 2);
        abort_if(abs($calculated - (float) $d['total']) > .01, 422, 'El total no coincide con los ítems.');
        $profile = $client->defaultArcaProfile;
        abort_unless($profile && $profile->active, 422, 'El cliente no tiene un perfil ARCA activo configurado.');
        abort_if(isset($d['profile']) && $d['profile'] !== $profile->slug, 422, 'El perfil ARCA no está permitido para este cliente.');
        $d['profile'] = $profile->slug;
        $fingerprint = hash('sha256', json_encode($this->canonicalize($d), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));

        $existing = Invoice::where('billing_client_id', $client->id)->where('idempotency_key', $idempotency)->first();
        if ($existing) return $this->replay($existing, $fingerprint);

        try {
            $invoice = DB::transaction(fn () => Invoice::create(['billing_client_id' => $client->id, 'idempotency_key' => $idempotency, 'request_fingerprint' => $fingerprint, 'arca_profile_id' => $profile->id, 'external_reference' => $d['external_reference'], 'status' => 'pending', 'invoice_type' => $d['invoice_type'], 'request_payload' => $d]));
        } catch (QueryException $e) {
            if (! in_array((string) $e->getCode(), ['23000', '23505'], true)) throw $e;
            $existing = Invoice::where('billing_client_id', $client->id)->where('idempotency_key', $idempotency)->firstOrFail();
            return $this->replay($existing, $fingerprint);
        }
        IssueInvoice::dispatch($invoice->id)->afterCommit();
        $events->record('invoice.received', 'Factura recibida y encolada.', ['invoice_id' => $invoice->id, 'client' => $client->slug, 'external_reference' => $invoice->external_reference]);

        return response()->json($this->resource($invoice), 202);
    }

    public function show(Request $request, Invoice $invoice)
    {
        $this->authorizeOwnership($request, $invoice);
        return response()->json($this->resource($invoice));
    }

    public function download(Request $request, Invoice $invoice)
    {
        $this->authorizeOwnership($request, $invoice);
        abort_unless($invoice->status === 'completed' && $invoice->pdf_path && Storage::exists($invoice->pdf_path), 404);

        return Storage::download($invoice->pdf_path, 'factura_'.$invoice->external_reference.'.pdf', ['Content-Type' => 'application/pdf']);
    }

    private function resource(Invoice $i): array
    {
        $i->loadMissing('billingClient');
        return ['id' => $i->id, 'external_reference' => $i->external_reference, 'status' => $i->status, 'source' => $i->billingClient?->slug ?? 'legacy', 'voucher_number' => $i->voucher_number, 'cae' => $i->cae, 'cae_expires_at' => $i->cae_expires_at?->format('Y-m-d'), 'emailed_at' => $i->emailed_at?->toIso8601String(), 'error' => $i->status === 'failed' ? $i->error : null, 'status_url' => route('api.invoices.show', $i), 'download_url' => $i->status === 'completed' ? route('api.invoices.download',$i) : null];
    }

    private function authorizeOwnership(Request $request, Invoice $invoice): void { abort_unless($invoice->billing_client_id === $request->attributes->get('billing_client')->id, 404); }
    private function replay(Invoice $invoice, string $fingerprint) { return $invoice->request_fingerprint === $fingerprint ? response()->json($this->resource($invoice), 200) : response()->json(['message' => 'La Idempotency-Key ya fue utilizada con una solicitud diferente.'], 409); }
    private function canonicalize(mixed $value): mixed { if (! is_array($value)) return $value; if (! array_is_list($value)) ksort($value); return array_map(fn ($item) => $this->canonicalize($item), $value); }
}
