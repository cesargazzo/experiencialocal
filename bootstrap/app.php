<?php

use App\Http\Middleware\EnsureIsAdmin;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\EnsureVerificationLevel;
use App\Services\SecurityLog;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            EnsurePasswordIsChanged::class,
        ]);

        $middleware->alias([
            'admin' => EnsureIsAdmin::class,
            'verified.level' => EnsureVerificationLevel::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Registro de seguridad: errores del servidor.
        $exceptions->report(function (Throwable $e): void {
            app(SecurityLog::class)->record('error.server', null, [
                'exception' => $e::class,
                'message' => Str::limit($e->getMessage(), 300),
                'file' => str_replace(base_path().'/', '', $e->getFile()).':'.$e->getLine(),
            ], null, 'danger');
        });

        // Registro de seguridad: accesos denegados, pedidos en exceso, formularios vencidos y rastreos.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            $log = app(SecurityLog::class);
            match (true) {
                $e->getStatusCode() === 403 => $log->record('access.forbidden', null, [], null, 'warning'),
                $e->getStatusCode() === 419 => $log->record('session.expired', null, [], null, 'info'),
                $e->getStatusCode() === 429 => $log->record('request.throttled', null, [], $request->input('email'), 'warning'),
                $e->getStatusCode() === 404 && $log->isProbe($request->path()) => $log->record('probe.suspicious', null, [], null, 'danger'),
                default => null,
            };

            return null; // Sigue la respuesta normal.
        });

        $exceptions->render(function (TokenMismatchException $e) {
            app(SecurityLog::class)->record('session.expired');

            return null;
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
