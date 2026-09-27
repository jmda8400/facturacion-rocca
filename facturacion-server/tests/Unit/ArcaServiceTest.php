<?php

namespace Tests\Unit;

use App\Services\ArcaService;
use ReflectionMethod;
use ReflectionObject;
use Tests\TestCase;

class ArcaServiceTest extends TestCase
{
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
}
