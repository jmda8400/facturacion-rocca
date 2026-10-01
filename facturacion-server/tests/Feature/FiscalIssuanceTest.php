<?php

namespace Tests\Feature;

use App\Jobs\IssueInvoice;
use App\Jobs\SendInvoiceEmail;
use App\Models\ArcaProfile;
use App\Models\BillingEvent;
use App\Models\Invoice;
use App\Services\ArcaService;
use App\Services\BillingEventLogger;
use App\Services\InvoiceRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class FiscalIssuanceTest extends TestCase
{
    use RefreshDatabase;

    private ArcaProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->profile = ArcaProfile::create(['slug' => 'rocca', 'name' => 'Rocca', 'cuit' => '20123456789', 'sales_point' => 1, 'business_name' => 'Rocca', 'address' => 'Bariloche', 'vat_condition' => 'RI', 'certificate_path' => 'x', 'private_key_path' => 'x', 'ta_path' => 'x']);
    }

    public function test_lost_authorization_response_is_reconciled_without_a_second_request(): void
    {
        $invoice = $this->invoice();
        $arca = $this->arca();
        $arca->shouldReceive('lastAuthorized')->once()->andReturn(10);
        $arca->shouldReceive('authorize')->once()->withArgs(fn ($p, $data, $number) => $number === 11)->andThrow(new RuntimeException('timeout'));

        $this->expectJobException(fn () => $this->run($invoice, $arca));
        $this->assertSame(11, $invoice->fresh()->voucher_number);

        $arca->shouldReceive('consult')->once()->withArgs(fn ($p, $type, $number) => $number === 11)->andReturn($this->authorization(11));
        $this->run($invoice, $arca);

        $invoice->refresh();
        $this->assertSame(11, $invoice->voucher_number);
        $this->assertSame('12345678901234', $invoice->cae);
        $this->assertSame('completed', $invoice->status);
    }

    public function test_timeout_before_authorization_retries_exactly_the_reserved_number(): void
    {
        $invoice = $this->invoice();
        $arca = $this->arca();
        $arca->shouldReceive('consult')->once()->andReturn(null);
        $arca->shouldReceive('lastAuthorized')->twice()->andReturn(20);
        $arca->shouldReceive('authorize')->once()->withArgs(fn ($p, $data, $number) => $number === 21)->andThrow(new RuntimeException('timeout'));
        $this->expectJobException(fn () => $this->run($invoice, $arca));
        $arca->shouldReceive('authorize')->once()->withArgs(fn ($p, $data, $number) => $number === 21)->andReturn($this->authorization(21));

        $this->run($invoice, $arca);

        $this->assertSame(21, $invoice->fresh()->voucher_number);
    }

    public function test_pdf_failure_after_cae_only_regenerates_the_pdf(): void
    {
        $invoice = $this->invoice();
        $arca = $this->arca();
        $arca->shouldReceive('lastAuthorized')->once()->andReturn(30);
        $arca->shouldReceive('authorize')->once()->andReturn($this->authorization(31));
        $badRenderer = Mockery::mock(InvoiceRenderer::class);
        $badRenderer->shouldReceive('render')->once()->andThrow(new RuntimeException('pdf failed'));
        $this->expectJobException(fn () => $this->run($invoice, $arca, $badRenderer));
        $this->assertSame('authorized', $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->cae);

        $this->run($invoice, $arca);
        $this->assertSame('completed', $invoice->fresh()->status);
    }

    public function test_email_failure_does_not_change_completed_fiscal_invoice(): void
    {
        Storage::fake();
        Mail::shouldReceive('raw')->once()->andThrow(new RuntimeException('smtp down'));
        $invoice = $this->invoice(['status' => 'completed', 'voucher_number' => 40, 'voucher_date' => '2026-09-27', 'cae' => '12345678901234', 'cae_expires_at' => '2026-10-07', 'pdf_path' => 'invoices/test.pdf']);
        Storage::put('invoices/test.pdf', 'pdf');

        $this->expectJobException(fn () => (new SendInvoiceEmail($invoice->id))->handle(app(BillingEventLogger::class)));

        $this->assertSame('completed', $invoice->fresh()->status);
        $this->assertSame('12345678901234', $invoice->fresh()->cae);
        $this->assertTrue(BillingEvent::where('event', 'invoice.email_failed')->exists());
    }

    public function test_invoice_email_has_bilingual_message_and_required_bcc_recipients(): void
    {
        Storage::fake();
        $invoice = $this->invoice([
            'status' => 'completed',
            'pdf_path' => 'invoices/test.pdf',
            'request_payload' => array_merge($this->invoicePayload(), ['email_to' => 'guest@example.com']),
        ]);
        Storage::put('invoices/test.pdf', 'pdf');

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $body, callable $callback): bool {
                $message = Mockery::mock(Message::class);
                $message->shouldReceive('to')->once()->with('guest@example.com')->andReturnSelf();
                $message->shouldReceive('subject')->once()->with('Factura Refugio Rocca')->andReturnSelf();
                $message->shouldReceive('bcc')->once()->withArgs(function (array $recipients): bool {
                    $this->assertContains('juanmanueldiazarbues@gmail.com', $recipients);
                    $this->assertContains('refugiorocca@gmail.com', $recipients);

                    return true;
                })->andReturnSelf();
                $message->shouldReceive('attach')->once()->andReturnSelf();
                $callback($message);

                return $body === "Adjuntamos tu factura electrónica.\nPlease find your electronic invoice attached.";
            });

        (new SendInvoiceEmail($invoice->id))->handle(app(BillingEventLogger::class));

        $this->assertNotNull($invoice->fresh()->emailed_at);
    }

    public function test_two_invoices_cannot_reserve_the_same_profile_type_and_number(): void
    {
        $first = $this->invoice(['voucher_number' => 51]);
        $second = $this->invoice();

        $this->expectException(\Illuminate\Database\QueryException::class);
        $second->update(['voucher_number' => $first->voucher_number]);
    }

    public function test_later_invoice_waits_behind_an_uncertain_reservation(): void
    {
        $this->invoice(['voucher_number' => 60, 'status' => 'fiscal_pending'], now()->subMinute());
        $later = $this->invoice();
        $arca = $this->arca();
        $arca->shouldNotReceive('lastAuthorized', 'authorize');

        $this->run($later, $arca);

        $this->assertNull($later->fresh()->voucher_number);
        $this->assertSame('fiscal_pending', $later->fresh()->status);
    }

    public function test_existing_mismatched_voucher_requires_manual_review_without_emission(): void
    {
        $invoice = $this->invoice(['voucher_number' => 70, 'status' => 'fiscal_pending']);
        $arca = $this->arca();
        $mismatch = $this->authorization(70);
        $mismatch['total'] = 999;
        $arca->shouldReceive('consult')->once()->andReturn($mismatch);
        $arca->shouldNotReceive('lastAuthorized', 'authorize');

        $this->run($invoice, $arca);

        $this->assertSame('review_required', $invoice->fresh()->status);
        $this->assertNull($invoice->fresh()->cae);
    }

    public function test_reconciliation_checks_available_fiscal_contract_fields(): void
    {
        $invoice = $this->invoice(['voucher_number' => 71, 'status' => 'fiscal_pending']);
        $arca = $this->arca();
        $mismatch = $this->authorization(71) + ['concept' => 2, 'vat_condition_id' => 5];
        $arca->shouldReceive('consult')->once()->andReturn($mismatch);
        $arca->shouldNotReceive('lastAuthorized', 'authorize');
        $this->run($invoice, $arca);
        $this->assertSame('review_required', $invoice->fresh()->status);
    }

    private function run(Invoice $invoice, ArcaService $arca, ?InvoiceRenderer $renderer = null): void
    {
        $renderer ??= tap(Mockery::mock(InvoiceRenderer::class), fn ($mock) => $mock->shouldReceive('render')->andReturn('invoices/test.pdf'));
        (new IssueInvoice($invoice->id))->handle($arca, $renderer, app(BillingEventLogger::class));
    }

    private function arca(): ArcaService
    {
        $mock = Mockery::mock(ArcaService::class)->makePartial();
        $mock->shouldReceive('voucherType')->andReturn(6);
        $mock->shouldReceive('expectedAmounts')->andReturn(['total' => 121.0, 'net' => 100.0, 'vat' => 21.0]);

        return $mock;
    }

    private function invoice(array $changes = [], $createdAt = null): Invoice
    {
        $payload = $this->invoicePayload();
        $invoice = Invoice::create(array_merge(['idempotency_key' => uniqid('key-'), 'arca_profile_id' => $this->profile->id, 'external_reference' => $payload['external_reference'], 'status' => 'pending', 'invoice_type' => 'B', 'request_payload' => $payload], $changes));
        if ($createdAt) {
            $invoice->timestamps = false;
            $invoice->created_at = $createdAt;
            $invoice->save();
            $invoice->timestamps = true;
        }

        return $invoice;
    }

    private function invoicePayload(): array
    {
        return ['external_reference' => uniqid('REF-'), 'invoice_type' => 'B', 'concept' => 1, 'customer' => ['name' => 'Ana', 'address' => 'Bariloche', 'vat_condition' => 'CF', 'vat_condition_id' => 5, 'document_type' => 96, 'document_number' => 12345678], 'items' => [['description' => 'Servicio', 'quantity' => 1, 'unit_price' => 121]], 'total' => 121, 'email_to' => null];
    }

    private function authorization(int $number): array
    {
        return ['type' => 6, 'sales_point' => 1, 'number' => $number, 'date' => '2026-09-27', 'total' => 121.0, 'net' => 100.0, 'vat' => 21.0, 'document_type' => 96, 'document_number' => 12345678, 'cae' => '12345678901234', 'cae_expires_at' => '2026-10-07'];
    }

    private function expectJobException(callable $callback): void
    {
        try {
            $callback();
            $this->fail('The job should have failed.');
        } catch (RuntimeException) {
            // Expected simulated infrastructure failure.
        }
    }
}
