<?php

namespace Tests\Feature;

use App\Models\ArcaProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_login_and_settings_share_the_service_visual_identity(): void
    {
        $sharedElements = ['Refugio Agostino Rocca', 'Panel de facturación', 'Administración de servicios'];

        foreach ($sharedElements as $element) {
            $this->get('/settings/login')->assertOk()->assertSee($element);
            $this->withSession(['settings_authenticated' => true])->get('/settings')->assertOk()->assertSee($element);
        }

        $this->get('/settings/login')
            ->assertSee('--brand:#f28c28', false)
            ->assertSee('--header:#212529', false);
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
            ->assertSee('Registrar punto de venta')
            ->assertSee('name="certificate"', false)
            ->assertSee('name="private_key"', false)
            ->assertSee('Próxima renovación del TA')
            ->assertSee('data-next-renewal=', false)
            ->assertDontSee('Cada consumidor de la API')
            ->assertDontSee('Razón social');
    }

    public function test_settings_can_register_a_point_of_sale_with_only_its_credentials(): void
    {
        Storage::fake();

        $this->withSession(['settings_authenticated' => true])
            ->post('/settings/profiles', [
                'name' => 'Refugio Rocca',
                'certificate' => UploadedFile::fake()->create('rocca.crt', 10),
                'private_key' => UploadedFile::fake()->create('rocca.key', 10),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Punto de venta registrado.');

        $profile = ArcaProfile::firstOrFail();
        $this->assertSame('Refugio Rocca', $profile->name);
        $this->assertSame('refugio-rocca', $profile->slug);
        Storage::assertExists('arca/refugio-rocca/certificate.crt');
        Storage::assertExists('arca/refugio-rocca/private.key');
    }

    public function test_point_of_sale_credentials_require_crt_and_key_extensions(): void
    {
        Storage::fake();

        $this->withSession(['settings_authenticated' => true])
            ->from('/settings')
            ->post('/settings/profiles', [
                'name' => 'Refugio Rocca',
                'certificate' => UploadedFile::fake()->create('rocca.txt', 10),
                'private_key' => UploadedFile::fake()->create('rocca.pem', 10),
            ])
            ->assertRedirect('/settings')
            ->assertSessionHasErrors(['certificate', 'private_key']);

        $this->assertDatabaseCount('arca_profiles', 0);
    }
}
