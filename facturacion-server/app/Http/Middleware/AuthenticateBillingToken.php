<?php

namespace App\Http\Middleware;

use App\Models\BillingAccessToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateBillingToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();
        $token = $plain ? BillingAccessToken::with('billingClient')->where('token_hash', hash('sha256', $plain))->first() : null;

        if (! $token) {
            return $this->unauthorized(null, 'Token inválido.');
        }
        if ($token->expires_at->isPast()) {
            return $this->unauthorized(['token_expired' => true], 'El token ha expirado.');
        }
        if ($token->revoked_at || ! $token->billingClient?->active) {
            return $this->unauthorized(null, 'Token inválido.');
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('billing_client', $token->billingClient);

        return $next($request);
    }

    private function unauthorized(?array $data, string $message): Response
    {
        return response()->json(['data' => $data, 'error' => 'Unauthorized', 'message' => $message, 'pagination' => null, 'status_code' => 401, 'success' => false], 401);
    }
}
