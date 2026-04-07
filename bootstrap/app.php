<?php

// ═══════════════════════════════════════════════════════════════════════════
// bootstrap/app.php  — Laravel 11 style
// Register custom middleware aliases here
// ═══════════════════════════════════════════════════════════════════════════

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register custom middleware aliases
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'role'       => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle 403 — show a friendly denied page
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 403) {
                return response()->view('errors.403', [], 403);
            }
        });
    })
    ->create();
