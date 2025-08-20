<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class UserLockedOut
{
    use Dispatchable, SerializesModels;

    /**
     * The locked out user.
     */
    public User $user;

    /**
     * The lockout context.
     */
    public array $context;

    /**
     * When the account will be unlocked.
     */
    public string $unlocksAt;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Request $request)
    {
        $this->user = $user;
        $this->unlocksAt = $user->locked_until ? $user->locked_until->toIso8601String() : '';
        $this->context = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
            'email' => $user->email,
            'failed_attempts' => $user->failed_login_attempts,
            'locked_until' => $this->unlocksAt,
        ];
    }
}
