<?php

namespace Tests\Unit;

use App\Models\ArcaProfile;
use App\Services\ArcaService;
use Mockery;
use ReflectionMethod;
use ReflectionObject;
use Tests\TestCase;

class ArcaServiceTest extends TestCase
{
    public function test_service_concept_sends_service_dates_and_explicit_vat_condition(): void
    {
        $request = $this->authorizeRequest($this->payload(2) + ['service_from' => '2026-12-10', 'service_to' => '2026-12-11', 'payment_due_date' => '2026-12-12']);
        $detail = $request['FeCAEReq']['FeDetReq']['FECAEDetRequest'][0];
        $this->assertSame(2, $detail['Concepto']);
        $this->assertSame(5, $detail['CondicionIVAReceptorId']);
        $this->assertSame('20261210', $detail['FchServDesde']);
        $this->assertSame('20261211', $detail['FchServHasta']);
        $this->assertSame('20261212', $detail['FchVtoPago']);
    }

    public function test_product_concept_does_not_send_service_dates(): void
    {
        $detail = $this->authorizeRequest($this->payload(1))['FeCAEReq']['FeDetReq']['FECAEDetRequest'][0];
        $this->assertSame(1, $detail['Concepto']);
        $this->assertArrayNotHasKey('FchServDesde', $detail);
        $this->assertArrayNotHasKey('FchServHasta', $detail);
        $this->assertArrayNotHasKey('FchVtoPago', $detail);
    }

    public function test_current_amount_calculation_is_fixed_at_twenty_one_percent_vat(): void
    {
        $this->assertSame(['total' => 121.0, 'net' => 100.0, 'vat' => 21.0], (new ArcaService)->expectedAmounts(['total' => 121]));
    }

    public function test_wsfe_client_uses_only_the_configured_secure_stream_context(): void
    {
        config()->set('billing.arca.wsfe_wsdl', __DIR__.'/../Fixtures/wsfe.wsdl');
        config()->set('billing.arca.wsfe_ssl_ciphers', 'TEST-CIPHERS');

        $method = new ReflectionMethod(ArcaService::class, 'wsfeClient');
        $client = $method->invoke(new ArcaService);
        $property = (new ReflectionObject($client))->getProperty('_stream_context');
        $options = stream_context_get_options($property->getValue($client));

        $this->assertSame('TEST-CIPHERS', $options['ssl']['ciphers']);
        $this->assertTrue($options['ssl']['verify_peer']);
        $this->assertTrue($options['ssl']['verify_peer_name']);
    }

    public function test_wsfe_cipher_default_is_openssl_legacy_compatibility_level(): void
    {
        $this->assertSame('DEFAULT@SECLEVEL=1', config('billing.arca.wsfe_ssl_ciphers'));
    }

    private function authorizeRequest(array $payload): array
    {
        $captured = null;
        $soap = Mockery::mock(\SoapClient::class);
        $soap->shouldReceive('FECAESolicitar')->once()->withArgs(function ($request) use (&$captured) { $captured = $request; return true; })->andReturn((object) ['FECAESolicitarResult' => (object) ['FeDetResp' => (object) ['FECAEDetResponse' => (object) ['Resultado' => 'A', 'CAE' => '123', 'CAEFchVto' => '20261220']]]]);
        $service = Mockery::mock(ArcaService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('wsfeClient')->once()->andReturn($soap);
        $service->shouldReceive('ticket')->once()->andReturn(simplexml_load_string('<loginTicketResponse><credentials><token>secret-token</token><sign>secret-sign</sign></credentials></loginTicketResponse>'));
        $service->authorize(new ArcaProfile(['cuit' => '20123456789', 'sales_point' => 1]), $payload, 1);
        return $captured;
    }

    private function payload(int $concept): array
    {
        return ['invoice_type' => 'B', 'concept' => $concept, 'customer' => ['document_type' => 99, 'document_number' => 0, 'vat_condition_id' => 5], 'total' => 121];
    }
}
