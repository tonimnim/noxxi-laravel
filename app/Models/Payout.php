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

    protected $fillable = [
        'organizer_id',
        'payout_reference',
        'gross_amount',
        'commission_amount',
        'processing_fee',
        'net_amount',
        'currency',
        'payout_method',
        'payment_details',
        'status',
        'period_start',
        'period_end',
        'transaction_ids',
        'transaction_count',
        'processor_reference',
        'failure_reason',
        'processed_by',
        'requested_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'processing_fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'payment_details' => 'array',
            'transaction_ids' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
            'requested_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($payout) {
            if (empty($payout->payout_reference)) {
                $payout->payout_reference = 'PO-' . strtoupper(Str::random(10));
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
}