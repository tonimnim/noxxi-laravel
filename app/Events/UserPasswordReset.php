<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserPasswordReset
{
    use Dispatchable, SerializesModels;

    /**
     * The user who reset their password.
     */
    public User $user;

    /**
     * The reset context.
     */
    public array $context;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->context = [
            'timestamp' => now()->toIso8601String(),
            'email' => $user->email,
            'user_id' => $user->id,
            'ip' => request()->ip(),
        ];
    }
}
