<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule event reminders to run every hour
Schedule::command('notifications:send-event-reminders --hours=24')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Schedule event reminders for same-day events (6 hours before)
Schedule::command('notifications:send-event-reminders --hours=6')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground();

// Clean old notifications once a day
Schedule::call(function () {
    app(\App\Services\NotificationService::class)->cleanOldNotifications(30);
})->daily()->at('03:00');

// Aggregate geographic data every 30 minutes for heat map
Schedule::job(new \App\Jobs\AggregateGeographicData())
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// Payout Monitoring and Reconciliation
// Monitor pending payouts twice daily - alerts admins of urgent approvals needed
Schedule::command('payouts:monitor-pending')
    ->twiceDaily(9, 15)  // Run at 9 AM and 3 PM
    ->withoutOverlapping()
    ->runInBackground();

// Reconcile payouts daily - checks status with gateway and detects stuck payouts
Schedule::command('payouts:reconcile')
    ->dailyAt('02:00')  // Run at 2 AM
    ->withoutOverlapping()
    ->runInBackground();

// Clean up expired bookings that were never paid
Schedule::command('bookings:cleanup-expired')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground();
