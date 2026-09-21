<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = (string) $request->bearerToken();
        $valid = collect(config('billing.api_keys'))->contains(fn ($key) => $key !== '' && hash_equals((string) $key, $provided));

        return $valid ? $next($request) : response()->json(['message' => 'API key inválida.'], 401);
    }
}
