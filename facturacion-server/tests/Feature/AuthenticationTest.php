<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
