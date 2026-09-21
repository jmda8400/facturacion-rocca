<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsAuthController
{
    public function form()
    {
        return view('auth.login');
    }

    public function login(Request $r)
    {
        $data = $r->validate(['username' => 'required', 'password' => 'required']);
        $password = (string) config('billing.settings_password');
        if ($password !== '' && hash_equals((string) config('billing.settings_username'), $data['username']) && hash_equals($password, $data['password'])) {
            $r->session()->regenerate();
            $r->session()->put('settings_authenticated', true);

            return redirect()->route('settings.index');
        }

return back()->withErrors(['username' => 'Credenciales incorrectas.']);
    }

    public function logout(Request $r)
    {
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('settings.login');
    }
}
