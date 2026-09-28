<?php

namespace Tests\Feature;

use App\Models\ArcaProfile;
use App\Models\Invoice;
use App\Services\ArcaService;
use App\Services\InvoiceRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_factura_b_contains_real_fiscal_customer_item_and_service_data(): void
    {
        $invoice = $this->invoice(2);

        $html = $this->renderView($invoice);

        $this->assertStringContainsString('FACTURA B', $html);
        $this->assertStringContainsString('Cod. 006', $html);
        $this->assertStringContainsString('Rocca Servicios S.A.S.', $html);
        $this->assertStringContainsString('CUIT:</span> 20123456789', $html);
        $this->assertStringContainsString('Av. Siempre Viva 123', $html);
        $this->assertStringContainsString('IVA Responsable Inscripto', $html);
        $this->assertStringContainsString('Ingresos Brutos:</span> 123-456789-0', $html);
        $this->assertStringContainsString('Fecha de Inicio de Actividades:</span> 15/03/2020', $html);
        $this->assertStringContainsString('Período Facturado Desde:</span> 01/09/2026', $html);
        $this->assertStringContainsString('Hasta:</span> 30/09/2026', $html);
        $this->assertStringContainsString('Fecha de Vto. para el pago:</span> 10/10/2026', $html);
        $this->assertStringContainsString('CUIT 30712345678', $html);
        $this->assertStringContainsString('Cliente Ejemplo S.A.', $html);
        $this->assertStringContainsString('Servicio de alojamiento', $html);
        $this->assertStringContainsString('IVA Contenido: $ 15.619,83', $html);
        $this->assertStringContainsString('Importe Total:', $html);
        $this->assertStringContainsString('$ 90.000,00', $html);
        $this->assertStringContainsString('data:image/png;base64,qr-test', $html);
        $this->assertStringContainsString('CAE N°:</span> 12345678901234', $html);
        $this->assertStringContainsString('Fecha de Vto. de CAE:</span> 08/10/2026', $html);
    }

    public function test_product_invoice_completely_hides_service_period(): void
    {
        $html = $this->renderView($this->invoice(1));

        $this->assertStringNotContainsString('Período Facturado Desde', $html);
        $this->assertStringNotContainsString('Fecha de Vto. para el pago', $html);
    }

    public function test_rerender_command_only_uses_stored_authorization_and_replaces_pdf_path(): void
    {
        $invoice = $this->invoice(1);
        $invoice->update(['pdf_path' => 'invoices/old.pdf']);

        $arca = Mockery::mock(ArcaService::class);
        $arca->shouldReceive('expectedAmounts')->once()->andReturn(['total' => 90000.0, 'net' => 74380.17, 'vat' => 15619.83]);
        $arca->shouldReceive('voucherType')->once()->with('B')->andReturn(6);
        $arca->shouldNotReceive('authorize', 'consult', 'lastAuthorized');
        $this->app->instance(ArcaService::class, $arca);

        $renderer = Mockery::mock(InvoiceRenderer::class);
        $renderer->shouldReceive('render')->once()->withArgs(function (Invoice $given, array $authorization) use ($invoice): bool {
            return $given->is($invoice)
                && $authorization['cae'] === '12345678901234'
                && $authorization['number'] === 2460
                && $authorization['vat'] === 15619.83;
        })->andReturn('invoices/new.pdf');
        $this->app->instance(InvoiceRenderer::class, $renderer);

        $this->artisan('billing:invoice:rerender', ['invoice-id' => $invoice->id])
            ->expectsOutput('PDF regenerado: invoices/new.pdf')
            ->assertSuccessful();

        $this->assertSame('invoices/new.pdf', $invoice->fresh()->pdf_path);
        $this->assertSame(2460, $invoice->fresh()->voucher_number);
        $this->assertSame('12345678901234', $invoice->fresh()->cae);
    }

    private function renderView(Invoice $invoice): string
    {
        return view('invoices.pdf', [
            'invoice' => $invoice,
            'payload' => $invoice->request_payload,
            'arca' => [
                'type' => 6,
                'number' => 2460,
                'date' => '2026-09-28',
                'total' => 90000,
                'vat' => 15619.83,
                'cae' => '12345678901234',
                'cae_expires_at' => '2026-10-08',
            ],
            'qr' => 'data:image/png;base64,qr-test',
        ])->render();
    }

    private function invoice(int $concept): Invoice
    {
        $profile = ArcaProfile::create([
            'slug' => 'rocca-pdf-'.$concept,
            'name' => 'Rocca',
            'cuit' => '20123456789',
            'sales_point' => 6,
            'business_name' => 'Rocca Servicios S.A.S.',
            'address' => 'Av. Siempre Viva 123',
            'vat_condition' => 'IVA Responsable Inscripto',
            'gross_income' => '123-456789-0',
            'activity_started_at' => '2020-03-15',
            'certificate_path' => 'cert.pem',
            'private_key_path' => 'key.pem',
            'ta_path' => 'ta.xml',
        ]);

        return Invoice::create([
            'idempotency_key' => 'pdf-'.$concept,
            'request_fingerprint' => 'fingerprint-'.$concept,
            'arca_profile_id' => $profile->id,
            'external_reference' => 'PDF-'.$concept,
            'status' => 'completed',
            'invoice_type' => 'B',
            'request_payload' => [
                'concept' => $concept,
                'service_from' => '2026-09-01',
                'service_to' => '2026-09-30',
                'payment_due_date' => '2026-10-10',
                'customer' => [
                    'document_type' => 'CUIT',
                    'document_number' => '30712345678',
                    'name' => 'Cliente Ejemplo S.A.',
                    'vat_condition' => 'IVA Responsable Inscripto',
                    'address' => 'Calle Cliente 456',
                ],
                'items' => [['description' => 'Servicio de alojamiento', 'quantity' => 2, 'unit_price' => 45000]],
                'total' => 90000,
            ],
            'voucher_number' => 2460,
            'voucher_date' => '2026-09-28',
            'cae' => '12345678901234',
            'cae_expires_at' => '2026-10-08',
        ])->load('profile');
    }
}
