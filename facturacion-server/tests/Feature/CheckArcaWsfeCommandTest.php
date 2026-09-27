<?php

namespace Tests\Feature;

use App\Models\ArcaProfile;
use App\Services\ArcaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CheckArcaWsfeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_only_queries_the_last_factura_b_number(): void
    {
        $profile = $this->profile();
        $service = Mockery::mock(ArcaService::class);
        $service->shouldReceive('lastAuthorized')->once()->withArgs(
            fn (ArcaProfile $given, int $type) => $given->is($profile) && $type === 6
        )->andReturn(42);
        $this->app->instance(ArcaService::class, $service);

        $this->artisan('arca:check-wsfe', ['--profile' => $profile->slug])
            ->expectsTable(
                ['Campo', 'Valor'],
                [
                    ['Perfil', 'sistema-de-reservas'],
                    ['Punto de venta', 3],
                    ['Tipo de comprobante', '6 (Factura B)'],
                    ['Último autorizado', 42],
                ]
            )->assertSuccessful();
    }

    public function test_command_fails_when_wsfe_does_not_respond(): void
    {
        $profile = $this->profile();
        $service = Mockery::mock(ArcaService::class);
        $service->shouldReceive('lastAuthorized')->once()->andThrow(new RuntimeException('sin respuesta'));
        $this->app->instance(ArcaService::class, $service);

        $this->artisan('arca:check-wsfe', ['--profile' => $profile->slug])
            ->expectsOutputToContain('WSFE no respondió correctamente')
            ->assertFailed();
    }

    private function profile(): ArcaProfile
    {
        return ArcaProfile::create([
            'slug' => 'sistema-de-reservas',
            'name' => 'Reservas',
            'cuit' => '20123456789',
            'sales_point' => 3,
            'certificate_path' => 'arca/cert.pem',
            'private_key_path' => 'arca/key.pem',
            'ta_path' => 'arca/ta.xml',
            'active' => true,
        ]);
    }
}
