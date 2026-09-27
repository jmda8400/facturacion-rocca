<?php

use App\Http\Middleware\AuthenticateBillingToken;
use App\Http\Middleware\AuthenticateSettings;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')->withMiddleware(function (Middleware $middleware) {
    // The container is only exposed through the host reverse proxy. Trust its
    // forwarded scheme/host so generated API URLs remain HTTPS in production.
    $middleware->trustProxies(at: '*');
    // This service has no browser clients. Removing CORS handling prevents the
    // framework default from advertising an open Access-Control-Allow-Origin.
    $middleware->remove(\Illuminate\Http\Middleware\HandleCors::class);
    $middleware->alias(['billing.token' => AuthenticateBillingToken::class, 'settings.auth' => AuthenticateSettings::class]);
})->withExceptions(function (Exceptions $exceptions) {
    $exceptions->shouldRenderJsonWhen(fn (Request $request, \Throwable $e): bool => $request->is('api/*') || $request->expectsJson());

    $exceptions->render(function (ValidationException $e, Request $request) {
        if (! $request->is('api/*')) {
            return null;
        }

        return response()->json([
            'data' => null,
            'error' => 'Validation Error',
            'message' => 'Los datos enviados no son válidos.',
            'errors' => $e->errors(),
            'pagination' => null,
            'status_code' => 422,
            'success' => false,
        ], 422);
    });

    $exceptions->render(function (\Throwable $e, Request $request) {
        if (! $request->is('api/*')) {
            return null;
        }

        $status = match (true) {
            $e instanceof ModelNotFoundException => 404,
            $e instanceof AuthenticationException => 401,
            $e instanceof HttpExceptionInterface => $e->getStatusCode(),
            default => 500,
        };

        [$error, $message] = match ($status) {
            401 => ['Unauthorized', 'No autorizado.'],
            404 => ['Not Found', 'Recurso no encontrado.'],
            409 => ['Conflict', 'La solicitud está en conflicto con el estado actual del recurso.'],
            422 => ['Validation Error', $e->getMessage() ?: 'Los datos enviados no son válidos.'],
            429 => ['Too Many Requests', 'Demasiadas solicitudes. Intentá nuevamente más tarde.'],
            500 => ['Internal Server Error', 'Ocurrió un error interno.'],
            503 => ['Service Unavailable', 'El servicio no está disponible temporalmente.'],
            default => [Response::$statusTexts[$status] ?? 'Error', 'No fue posible procesar la solicitud.'],
        };

        $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

        return response()->json([
            'data' => null,
            'error' => $error,
            'message' => $message,
            'pagination' => null,
            'status_code' => $status,
            'success' => false,
        ], $status, $headers);
    });
})->create();
