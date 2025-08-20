<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organizer extends Model
{
    use HasFactory, HasUuids;

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
        'user_id',
        'business_name',
        'business_type',
        'business_description',
        'business_logo_url',
        'business_country',
        'business_address',
        'business_timezone',
        'payment_methods',
        'default_currency',
        'total_events',
        'total_tickets_sold',
        'total_revenue',
        'rating',
        'total_reviews',
        'api_key',
        'webhook_url',
        'webhook_secret',
        'webhook_events',
        'commission_rate',
        'settlement_period_days',
        'auto_approve_events',
        'is_active',
        'is_featured',
        'is_verified',
        'verification_status',
        'verified_at',
        'metadata',
        'approved_at',
        'approved_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'api_key',
        'webhook_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_methods' => 'array',
            'total_revenue' => 'array',
            'webhook_events' => 'array',
            'metadata' => 'array',
            'rating' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_verified' => 'boolean',
            'auto_approve_events' => 'boolean',
            'approved_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($organizer) {
            if (empty($organizer->api_key)) {
                $organizer->api_key = 'noxxi_'.Str::random(32);
            }
            if (empty($organizer->webhook_secret)) {
                $organizer->webhook_secret = Str::random(40);
            }
        });
    }

    /**
     * Get the user that owns the organizer profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who approved this organizer.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the events for the organizer.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get active events for the organizer.
     */
    public function activeEvents(): HasMany
    {
        return $this->hasMany(Event::class)->where('is_active', true);
    }

    /**
     * Get the managers (ticket scanners) for this organizer.
     */
    public function managers(): HasMany
    {
        return $this->hasMany(OrganizerManager::class);
    }

    /**
     * Get active managers (ticket scanners) for this organizer.
     */
    public function activeManagers(): HasMany
    {
        return $this->hasMany(OrganizerManager::class)->active();
    }

    /**
     * Get transactions for this organizer.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get completed transactions for this organizer.
     */
    public function completedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->completed();
    }

    /**
     * Get payouts for this organizer.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    /**
     * Check if organizer is approved.
     */
    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * Check if organizer has a specific payment method enabled.
     */
    public function hasPaymentMethod(string $provider): bool
    {
        if (! is_array($this->payment_methods)) {
            return false;
        }

        foreach ($this->payment_methods as $method) {
            if ($method['provider'] === $provider && $method['enabled']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get primary payment method.
     */
    public function getPrimaryPaymentMethod(): ?array
    {
        if (! is_array($this->payment_methods)) {
            return null;
        }

        foreach ($this->payment_methods as $method) {
            if ($method['is_primary'] ?? false) {
                return $method;
            }
        }

        // Return first enabled method if no primary is set
        foreach ($this->payment_methods as $method) {
            if ($method['enabled'] ?? false) {
                return $method;
            }
        }

        return null;
    }

    /**
     * Add revenue to organizer.
     */
    public function addRevenue(string $currency, float $amount): void
    {
        $revenue = $this->total_revenue ?? [];
        $revenue[$currency] = ($revenue[$currency] ?? 0) + $amount;

        $this->update([
            'total_revenue' => $revenue,
            'total_tickets_sold' => $this->total_tickets_sold + 1,
        ]);
    }

    /**
     * Generate new API key.
     */
    public function regenerateApiKey(): string
    {
        $newKey = 'noxxi_'.Str::random(32);
        $this->update(['api_key' => $newKey]);

        return $newKey;
    }

    /**
     * Generate new webhook secret.
     */
    public function regenerateWebhookSecret(): string
    {
        $newSecret = Str::random(40);
        $this->update(['webhook_secret' => $newSecret]);

        return $newSecret;
    }
}
