<?php

namespace App\Listeners;

use App\Events\UserFailedLogin;
use App\Events\UserLockedOut;
use App\Events\UserLoggedIn;
use App\Events\UserPasswordReset;
use Illuminate\Support\Facades\Log;

class LogAuthenticationActivity
{
    /**
     * Handle user login events.
     */
    public function handleUserLogin(UserLoggedIn $event): void
    {
        Log::channel('auth')->info('User logged in', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'role' => $event->user->role,
            'ip' => $event->context['ip'],
            'user_agent' => $event->context['user_agent'],
            'timestamp' => $event->context['timestamp'],
        ]);
    }

    /**
     * Handle failed login events.
     */
    public function handleFailedLogin(UserFailedLogin $event): void
    {
        Log::channel('auth')->warning('Failed login attempt', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'attempts' => $event->attemptCount,
            'ip' => $event->context['ip'],
            'user_agent' => $event->context['user_agent'],
            'timestamp' => $event->context['timestamp'],
        ]);

        // Send alert if multiple failed attempts
        if ($event->attemptCount >= 3) {
            Log::channel('auth')->alert('Multiple failed login attempts detected', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'attempts' => $event->attemptCount,
                'ip' => $event->context['ip'],
            ]);
        }
    }

    /**
     * Handle account lockout events.
     */
    public function handleAccountLockout(UserLockedOut $event): void
    {
        Log::channel('auth')->critical('User account locked', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'failed_attempts' => $event->context['failed_attempts'],
            'locked_until' => $event->context['locked_until'],
            'ip' => $event->context['ip'],
            'user_agent' => $event->context['user_agent'],
            'timestamp' => $event->context['timestamp'],
        ]);

        // In production, this could trigger an email notification
        // to the user about their account being locked
    }

    /**
     * Handle password reset events.
     */
    public function handlePasswordReset(UserPasswordReset $event): void
    {
        Log::channel('auth')->info('Password reset completed', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip' => $event->context['ip'],
            'timestamp' => $event->context['timestamp'],
        ]);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            UserLoggedIn::class => 'handleUserLogin',
            UserFailedLogin::class => 'handleFailedLogin',
            UserLockedOut::class => 'handleAccountLockout',
            UserPasswordReset::class => 'handlePasswordReset',
        ];
    }
}
