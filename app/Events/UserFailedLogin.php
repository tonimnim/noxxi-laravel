<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class UserFailedLogin
{
    use Dispatchable, SerializesModels;

    /**
     * The user that failed to login.
     */
    public User $user;

    /**
     * The request context.
     */
    public array $context;

    /**
     * Number of failed attempts.
     */
    public int $attemptCount;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Request $request)
    {
        $this->user = $user;
        $this->attemptCount = $user->failed_login_attempts;
        $this->context = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
            'email' => $user->email,
            'attempts' => $this->attemptCount,
        ];
    }
}
