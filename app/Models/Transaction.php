<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
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
        'type',
        'booking_id',
        'organizer_id',
        'user_id',
        'payout_id',
        'amount',
        'currency',
        'commission_amount',
        'gateway_fee',
        'payment_processing_fee',
        'paystack_fee',
        'platform_commission',
        'payout_fee',
        'net_amount',
        'payment_gateway',
        'payment_method',
        'payment_reference',
        'gateway_reference',
        'status',
        'failure_reason',
        'processed_at',
        'reversed_at',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'gateway_fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'metadata' => 'array',
            'processed_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    /**
     * Transaction types.
     */
    const TYPE_TICKET_SALE = 'ticket_sale';

    const TYPE_REFUND = 'refund';

    const TYPE_PAYOUT = 'payout';

    const TYPE_COMMISSION = 'commission';

    const TYPE_FEE = 'fee';

    const TYPE_WITHDRAWAL = 'withdrawal';

    /**
     * Payment gateways.
     */
    const GATEWAY_PAYSTACK = 'paystack';

    const GATEWAY_MPESA = 'mpesa';

    const GATEWAY_BANK = 'bank_transfer';

    const GATEWAY_CASH = 'cash';

    const GATEWAY_FREE = 'free';

    /**
     * Transaction statuses.
     */
    const STATUS_PENDING = 'pending';

    const STATUS_PROCESSING = 'processing';

    const STATUS_COMPLETED = 'completed';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_REVERSED = 'reversed';

    /**
     * Get the booking associated with the transaction.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the organizer associated with the transaction.
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * Get the user associated with the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate net amount after commission and fees.
     */
    public function calculateNetAmount(): float
    {
        $net = $this->amount;

        if ($this->commission_amount) {
            $net -= $this->commission_amount;
        }

        if ($this->gateway_fee) {
            $net -= $this->gateway_fee;
        }

        return $net;
    }

    /**
     * Mark transaction as completed.
     */
    public function markAsCompleted(?string $gatewayReference = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
            'gateway_reference' => $gatewayReference,
        ]);
    }

    /**
     * Mark transaction as failed.
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
            'processed_at' => now(),
        ]);
    }

    /**
     * Reverse transaction.
     */
    public function reverse(): void
    {
        $this->update([
            'status' => self::STATUS_REVERSED,
            'reversed_at' => now(),
        ]);
    }

    /**
     * Check if transaction is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if transaction is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Get M-Pesa specific data from metadata.
     */
    public function getMpesaData(): array
    {
        if ($this->payment_gateway !== self::GATEWAY_MPESA) {
            return [];
        }

        return [
            'receipt_number' => $this->metadata['mpesa_receipt_number'] ?? null,
            'phone_number' => $this->metadata['mpesa_phone_number'] ?? null,
            'transaction_date' => $this->metadata['mpesa_transaction_date'] ?? null,
        ];
    }

    /**
     * Get Paystack specific data from metadata.
     */
    public function getPaystackData(): array
    {
        if ($this->payment_gateway !== self::GATEWAY_PAYSTACK) {
            return [];
        }

        return [
            'reference' => $this->gateway_reference,
            'card_last4' => $this->metadata['card_last4'] ?? null,
            'card_type' => $this->metadata['card_type'] ?? null,
            'bank' => $this->metadata['bank'] ?? null,
        ];
    }

    /**
     * Scope for completed transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for failed transactions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for transactions by gateway.
     */
    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    /**
     * Get formatted amount with currency.
     */
    public function getFormattedAmountAttribute(): string
    {
        return $this->currency.' '.number_format($this->amount, 2);
    }

    /**
     * Get human-readable status.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REVERSED => 'Reversed',
            default => 'Unknown',
        };
    }

    /**
     * Get human-readable payment gateway.
     */
    public function getGatewayLabelAttribute(): string
    {
        return match ($this->payment_gateway) {
            self::GATEWAY_PAYSTACK => 'Paystack',
            self::GATEWAY_MPESA => 'M-Pesa',
            self::GATEWAY_CRYPTO => 'Cryptocurrency',
            self::GATEWAY_BANK => 'Bank Transfer',
            self::GATEWAY_CASH => 'Cash',
            self::GATEWAY_FREE => 'Free',
            default => 'Unknown',
        };
    }
}
