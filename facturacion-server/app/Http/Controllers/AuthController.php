<?php

namespace App\Http\Controllers;

use App\Models\BillingAccessToken;
use App\Models\BillingClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController
{
    public function login(Request $request)
    {
        $data = $request->validate(['username' => 'required|string|max:100', 'password' => 'required|string|max:1000']);
        $client = BillingClient::where('username', $data['username'])->first();

        if (! $client || ! $client->active || ! Hash::check($data['password'], $client->password_hash)) {
            return $this->response(null, 'Unauthorized', 'Credenciales inválidas.', 401);
        }

        $plain = bin2hex(random_bytes(32));
        $expiresAt = now()->addHours(config('billing.token_ttl_hours'));
        BillingAccessToken::create([
            'billing_client_id' => $client->id,
            'token_hash' => hash('sha256', $plain),
            'expires_at' => $expiresAt,
        ]);

        return $this->response(['token' => $plain, 'expires_at' => $expiresAt->toIso8601String()], null, null, 200);
    }

    private function response(mixed $data, ?string $error, ?string $message, int $status)
    {
        return response()->json(['data' => $data, 'error' => $error, 'message' => $message, 'pagination' => null, 'status_code' => $status, 'success' => $status < 400], $status);
    }
}
