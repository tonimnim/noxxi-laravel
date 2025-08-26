<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add SecurityHeaders middleware to all responses
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->alias([
            'organizer' => \App\Http\Middleware\EnsureUserIsOrganizer::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        // Exclude webhook endpoints from CSRF protection
        $middleware->validateCsrfTokens(except: [
            'api/webhooks/paystack',
            'api/webhooks/mpesa',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
