<?php

use App\Http\Middleware\AuthenticateBillingToken;
use App\Http\Middleware\AuthenticateSettings;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')->withMiddleware(function (Middleware $middleware) {
    // The container is only exposed through the host reverse proxy. Trust its
    // forwarded scheme/host so generated API URLs remain HTTPS in production.
    $middleware->trustProxies(at: '*');
    $middleware->alias(['billing.token' => AuthenticateBillingToken::class, 'settings.auth' => AuthenticateSettings::class]);
})->withExceptions(fn (Exceptions $exceptions) => null)->create();
