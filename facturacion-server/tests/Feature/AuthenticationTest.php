<?php

namespace Tests\Feature;

use App\Models\ArcaProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_requires_login(): void
    {
        $this->get('/settings')->assertRedirect('/settings/login');
    }

    public function test_valid_settings_credentials_create_session(): void
    {
        config(['billing.settings_username' => 'facturacion', 'billing.settings_password' => 'frias']);
        $this->post('/settings/login', ['username' => 'facturacion', 'password' => 'frias'])->assertRedirect('/settings');
        $this->get('/settings')->assertOk();
    }

    public function test_api_rejects_missing_key(): void
    {
        config(['billing.api_keys' => ['secret']]);
        $this->postJson('/api/v1/invoices', [])->assertUnauthorized();
    }

    public function test_settings_lists_points_of_sale_with_ta_countdown(): void
    {
        Storage::fake();
        Storage::put('arca/rocca/TA.xml', '<ta/>');
        ArcaProfile::create([
            'slug' => 'rocca',
            'name' => 'Rocca',
            'cuit' => '20123456789',
            'sales_point' => 1,
            'business_name' => 'Refugio Rocca',
            'address' => 'Bariloche',
            'vat_condition' => 'Responsable Inscripto',
            'certificate_path' => 'arca/rocca/certificate.crt',
            'private_key_path' => 'arca/rocca/private.key',
            'ta_path' => 'arca/rocca/TA.xml',
        ]);

        $this->withSession(['settings_authenticated' => true])
            ->get('/settings')
            ->assertOk()
            ->assertSee('Puntos de Venta')
            ->assertSee('Próxima renovación del TA')
            ->assertSee('data-next-renewal=', false)
            ->assertDontSee('Cada consumidor de la API')
            ->assertDontSee('Razón social');
    }
}
