<?php

use Illuminate\Console\Scheduling\Schedule;
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
        // Trust proxies for proper IP and protocol detection
        $middleware->trustProxies(at: [
            '172.16.0.0/12', // Docker network
            '127.0.0.1',
        ]);

        // Add SecurityHeaders middleware to all responses
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->alias([
            'organizer' => \App\Http\Middleware\EnsureUserIsOrganizer::class,
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'cache.static' => \App\Http\Middleware\CacheStaticPages::class,
            'can.scan' => \App\Http\Middleware\CanScanTickets::class,
        ]);

        // Exclude webhook endpoints from CSRF protection
        $middleware->validateCsrfTokens(except: [
            'api/webhooks/paystack',
            'api/webhooks/mpesa',
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Expire tickets every hour
        $schedule->command('tickets:expire')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/ticket-expiration.log'));

        // Optional: Run at specific times for less server load
        // $schedule->command('tickets:expire')
        //     ->dailyAt('02:00')
        //     ->withoutOverlapping()
        //     ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
