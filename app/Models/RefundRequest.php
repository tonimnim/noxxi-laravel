<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRequest extends Model
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
        'booking_id',
        'user_id',
        'reason',
        'requested_amount',
        'approved_amount',
        'currency',
        'status',
        'reviewed_by',
        'processed_by',
        'review_notes',
        'rejection_reason',
        'transaction_id',
        'customer_message',
        'admin_response',
        'reviewed_at',
        'approved_at',
        'rejected_at',
        'processed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Refund request statuses.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWING = 'reviewing';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PROCESSED = 'processed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the booking for this refund request.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user who requested the refund.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed this request.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the admin who processed this request.
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the refund transaction.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Check if request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if request is processed.
     */
    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    /**
     * Start reviewing the request.
     */
    public function startReview(User $admin): void
    {
        $this->update([
            'status' => self::STATUS_REVIEWING,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Approve the refund request.
     */
    public function approve(User $admin, float $approvedAmount = null, string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_amount' => $approvedAmount ?? $this->requested_amount,
            'reviewed_by' => $admin->id,
            'review_notes' => $notes,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject the refund request.
     */
    public function reject(User $admin, string $reason, string $customerResponse = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $admin->id,
            'rejection_reason' => $reason,
            'admin_response' => $customerResponse,
            'rejected_at' => now(),
        ]);
    }

    /**
     * Process the approved refund.
     */
    public function processRefund(Transaction $refundTransaction, User $admin = null): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSED,
            'transaction_id' => $refundTransaction->id,
            'processed_by' => $admin?->id,
            'processed_at' => now(),
        ]);
    }

    /**
     * Cancel the request (by customer).
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Get formatted requested amount.
     */
    public function getFormattedRequestedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->requested_amount, 2);
    }

    /**
     * Get formatted approved amount.
     */
    public function getFormattedApprovedAmountAttribute(): string
    {
        if (!$this->approved_amount) {
            return '-';
        }
        return $this->currency . ' ' . number_format($this->approved_amount, 2);
    }

    /**
     * Check if this is a full refund.
     */
    public function isFullRefund(): bool
    {
        return $this->requested_amount >= $this->booking->total_amount;
    }

    /**
     * Check if this is a partial refund.
     */
    public function isPartialRefund(): bool
    {
        return $this->requested_amount < $this->booking->total_amount;
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for processed requests.
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', self::STATUS_PROCESSED);
    }
}