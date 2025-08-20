<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn
{
    use Dispatchable, SerializesModels;

    /**
     * The authenticated user.
     */
    public User $user;

    /**
     * The request instance.
     */
    public array $context;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Request $request)
    {
        $this->user = $user;
        $this->context = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
        ];
    }
}
