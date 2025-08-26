<?php

namespace App\Providers;

use App\Channels\SmsChannel;
use App\Http\Responses\LogoutResponse;
use App\Listeners\LogAuthenticationActivity;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Passport::ignoreRoutes();

        // Bind custom logout response for all Filament panels
        $this->app->bind(LogoutResponseContract::class, LogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use our custom Passport Client model
        Passport::useClientModel(\App\Models\PassportClient::class);

        // Set tokens to never expire (100 years)
        Passport::tokensExpireIn(now()->addYears(100));
        Passport::refreshTokensExpireIn(now()->addYears(100));
        Passport::personalAccessTokensExpireIn(now()->addYears(100));

        $this->configureRateLimiting();

        // Register event subscribers
        Event::subscribe(LogAuthenticationActivity::class);

        // Register custom notification channel for SMS
        Notification::extend('sms', function ($app) {
            return new SmsChannel;
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Rate limiting for authentication attempts
        RateLimiter::for('auth', function (Request $request) {
            $email = $request->input('email') ?: $request->ip();

            return Limit::perMinute(5)->by($email)->response(function () {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Too many login attempts. Please try again later.',
                ], 429);
            });
        });

        // Rate limiting for verification code requests
        RateLimiter::for('verification', function (Request $request) {
            $identifier = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(3)->by($identifier)->response(function () {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Too many verification attempts. Please wait before trying again.',
                ], 429);
            });
        });

        // Rate limiting for password reset requests
        RateLimiter::for('password-reset', function (Request $request) {
            $email = $request->input('email') ?: $request->ip();

            return Limit::perHour(3)->by($email)->response(function () {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Too many password reset attempts. Please try again later.',
                ], 429);
            });
        });

        // General API rate limiting (existing)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
