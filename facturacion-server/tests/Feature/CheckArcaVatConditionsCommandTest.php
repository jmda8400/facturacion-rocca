<?php

namespace Tests\Feature;

use App\Models\ArcaProfile;
use App\Services\ArcaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CheckArcaVatConditionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_only_queries_and_displays_vat_conditions(): void
    {
        $profile = $this->profile();
        $service = Mockery::mock(ArcaService::class);
        $service->shouldReceive('vatConditions')->once()->withArgs(fn (ArcaProfile $given, string $class) => $given->is($profile) && $class === 'B')->andReturn([
            ['id' => 5, 'description' => 'Consumidor Final', 'class' => 'B'],
        ]);
        $service->shouldNotReceive('authorize');
        $this->app->instance(ArcaService::class, $service);

        $this->artisan('arca:check-vat-conditions', ['--profile' => $profile->slug, '--class' => 'B'])
            ->expectsTable(['ID', 'Descripción', 'Clase permitida'], [[5, 'Consumidor Final', 'B']])
            ->assertSuccessful();
    }

    public function test_command_failure_does_not_expose_credentials(): void
    {
        $service = Mockery::mock(ArcaService::class);
        $service->shouldReceive('vatConditions')->once()->andThrow(new RuntimeException('Token=secret Sign=secret'));
        $this->app->instance(ArcaService::class, $service);

        $this->artisan('arca:check-vat-conditions', ['--profile' => $this->profile()->slug, '--class' => 'B'])
            ->doesntExpectOutputToContain('secret')
            ->assertFailed();
    }

    private function profile(): ArcaProfile
    {
        return ArcaProfile::create(['slug' => 'sistema-de-reservas', 'name' => 'Reservas', 'cuit' => '20123456789', 'sales_point' => 3, 'certificate_path' => 'cert.pem', 'private_key_path' => 'key.pem', 'ta_path' => 'ta.xml', 'active' => true]);
    }
}
