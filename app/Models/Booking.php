<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
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
        'event_id',
        'booking_reference',
        'quantity',
        'ticket_quantity',
        'ticket_types',
        'subtotal',
        'service_fee',
        'total_amount',
        'currency',
        'status',
        'payment_status',
        'payment_method',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_details',
        'notes',
        'expires_at',
        'cancelled_at',
        'refunded_at',
        'confirmed_at',
        'payment_fee',
        'discount_amount',
        'payment_reference',
        'payment_provider_data',
        'promo_code',
        'promo_details',
        'booking_metadata',
        'ip_address',
        'user_agent',
        'booking_source',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ticket_types' => 'array',
            'customer_details' => 'array',
            'booking_metadata' => 'array',
            'payment_provider_data' => 'array',
            'promo_details' => 'array',
            'subtotal' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'payment_fee' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($booking) {
            if (empty($booking->booking_reference)) {
                $booking->booking_reference = 'BK'.strtoupper(Str::random(8));
            }
        });
    }

    /**
     * Get the user that owns the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the event that the booking is for.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the tickets for the booking.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the transactions for the booking.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Scope for paid bookings.
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Check if booking has checked-in tickets.
     */
    public function hasCheckedInTickets(): bool
    {
        return $this->tickets()->where('checked_in', true)->exists();
    }

    /**
     * Get the main payment transaction.
     */
    public function paymentTransaction()
    {
        return $this->hasOne(Transaction::class)
            ->where('type', Transaction::TYPE_TICKET_SALE)
            ->latest();
    }

    /**
     * Get refund transactions.
     */
    public function refundTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class)
            ->where('type', Transaction::TYPE_REFUND);
    }

    /**
     * Get refund requests for this booking.
     */
    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }

    /**
     * Check if booking is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if booking is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if booking is refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Check if payment is complete.
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Cancel the booking.
     */
    public function cancel(?string $reason = null): void
    {
        $updates = [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ];

        // Only add cancelled_reason if the column exists and reason is provided
        if ($reason && in_array('cancelled_reason', $this->fillable)) {
            $updates['cancelled_reason'] = $reason;
        }

        $this->update($updates);

        // Cancel all tickets
        $this->tickets()->update(['status' => 'cancelled']);
    }
}
