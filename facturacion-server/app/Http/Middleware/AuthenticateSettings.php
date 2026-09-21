<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSettings
{
    public function handle(Request $r, Closure $next): Response
    {
        if ($r->session()->get('settings_authenticated') === true) {
            return $next($r);
        }

return redirect()->route('settings.login');
    }
}
