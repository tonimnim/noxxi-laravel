<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
{
    use HasFactory, Notifiable, HasApiTokens, HasUuids;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'password',
        'role',
        'avatar_url',
        'country_code',
        'city',
        'notification_preferences',
        'metadata',
        'is_active',
        'is_verified',
        'email_verified_at',
        'phone_verified_at',
        'last_active_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<string>
     */
    protected $appends = ['name'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_active_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
            'metadata' => 'array',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is an organizer
     */
    public function isOrganizer(): bool
    {
        return $this->role === 'organizer';
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Update last active timestamp
     */
    public function updateLastActive(): void
    {
        $this->update(['last_active_at' => now()]);
    }

    /**
     * Get the organizer profile if user is an organizer
     */
    public function organizer()
    {
        return $this->hasOne(Organizer::class, 'user_id');
    }

    /**
     * Determine if the user can access the Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Check email verification first
        if (!$this->hasVerifiedEmail()) {
            return false;
        }

        // Route users to appropriate panels based on their role
        return match ($panel->getId()) {
            'admin' => $this->role === 'admin',
            'organizer' => $this->role === 'organizer',
            'user' => $this->role === 'user',
            default => false,
        };
    }

    /**
     * Get the user's name for Filament
     */
    public function getFilamentName(): string
    {
        return $this->full_name ?? $this->email ?? 'User';
    }

    /**
     * Get name attribute for Filament compatibility
     */
    public function getNameAttribute(): string
    {
        return $this->full_name ?? $this->email ?? 'User';
    }

    /**
     * Get scanner permissions for this user.
     */
    public function scannerPermissions(): HasMany
    {
        return $this->hasMany(OrganizerManager::class, 'user_id');
    }

    /**
     * Get active scanner permissions for this user.
     */
    public function activeScannerPermissions(): HasMany
    {
        return $this->hasMany(OrganizerManager::class, 'user_id')->active();
    }

    /**
     * Check if user can scan tickets for a specific organizer.
     */
    public function canScanForOrganizer(string $organizerId): bool
    {
        return $this->activeScannerPermissions()
            ->where('organizer_id', $organizerId)
            ->where('can_scan_tickets', true)
            ->exists();
    }

    /**
     * Check if user can scan tickets for a specific event.
     */
    public function canScanForEvent(string $eventId): bool
    {
        // First check if user owns the organizer of this event
        $event = \App\Models\Event::find($eventId);
        if (!$event) {
            return false;
        }

        // Organizer owner can always scan their own events
        if ($event->organizer->user_id === $this->id) {
            return true;
        }

        // Check scanner permissions
        return $this->activeScannerPermissions()
            ->where('organizer_id', $event->organizer_id)
            ->where('can_scan_tickets', true)
            ->where(function ($query) use ($eventId) {
                $query->whereNull('event_ids')
                    ->orWhereJsonContains('event_ids', $eventId);
            })
            ->exists();
    }

    /**
     * Get transactions for this user.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get bookings for this user.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
