<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payout extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_PROCESSING = 'processing';

    const STATUS_PAID = 'paid';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'organizer_id',
        'reference_number',
        'gross_amount',
        'commission_deducted',
        'fees_deducted',
        'requested_amount',
        'platform_commission',
        'commission_amount',
        'payout_fee',
        'fee_absorbed',
        'net_amount',
        'currency',
        'type',
        'payout_method',
        'payment_method',
        'payment_reference',
        'bank_reference',
        'payout_details',
        'status',
        'booking_ids',
        'transaction_ids',
        'transaction_count',
        'failure_reason',
        'metadata',
        'requested_at',
        'processed_at',
        'processed_by',
        'completed_at',
        'paid_at',
        'period_start',
        'period_end',
        'hold_reason',
        'held_by',
        'held_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
        'admin_notes',
        'transaction_reference',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'commission_deducted' => 'decimal:2',
            'fees_deducted' => 'decimal:2',
            'requested_amount' => 'decimal:2',
            'platform_commission' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'payout_fee' => 'decimal:2',
            'fee_absorbed' => 'boolean',
            'net_amount' => 'decimal:2',
            'payout_details' => 'array',
            'booking_ids' => 'array',
            'transaction_ids' => 'array',
            'metadata' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
            'paid_at' => 'datetime',
            'approved_at' => 'datetime',
            'held_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($payout) {
            if (empty($payout->reference_number)) {
                $payout->reference_number = 'PO-'.strtoupper(Str::random(10));
            }
        });
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    // Accessor to handle both 'reference' and 'reference_number'
    public function getReferenceAttribute()
    {
        return $this->reference_number;
    }

    // Mutator to handle setting 'reference' as 'reference_number'
    public function setReferenceAttribute($value)
    {
        $this->reference_number = $value;
    }
    
    // Accessor to handle both 'payment_method' and 'payout_method'
    public function getPaymentMethodAttribute()
    {
        return $this->payout_method;
    }

    // Mutator to handle setting 'payment_method' as 'payout_method'
    public function setPaymentMethodAttribute($value)
    {
        $this->payout_method = $value;
    }
    
    // Accessor to handle both 'commission_amount' and 'commission_deducted'
    public function getCommissionAmountAttribute()
    {
        // First check if commission_amount column exists and has value
        if ($this->attributes['commission_amount'] ?? null) {
            return $this->attributes['commission_amount'];
        }
        // Fall back to commission_deducted
        return $this->commission_deducted ?? $this->platform_commission ?? 0;
    }

    // Mutator to handle setting 'commission_amount'
    public function setCommissionAmountAttribute($value)
    {
        $this->attributes['commission_amount'] = $value;
        // Also set commission_deducted for backward compatibility
        $this->commission_deducted = $value;
    }
}
