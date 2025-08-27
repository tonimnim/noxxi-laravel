<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
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
        'event_id',
        'ticket_code',
        'ticket_hash',
        'ticket_type',
        'price',
        'currency',
        'seat_number',
        'seat_section',
        'holder_name',
        'holder_email',
        'holder_phone',
        'assigned_to',
        'status',
        'used_at',
        'used_by',
        'entry_gate',
        'device_fingerprint',
        'transferred_from',
        'transferred_to',
        'transferred_at',
        'transfer_reason',
        'special_requirements',
        'notes',
        'valid_from',
        'valid_until',
        'offline_validation_data',
        'metadata',
        'cancelled_at',
        'cancelled_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'used_at' => 'datetime',
            'transferred_at' => 'datetime',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'cancelled_at' => 'datetime',
            'offline_validation_data' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the booking that owns the ticket.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the event that the ticket is for.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user assigned to this ticket.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who used/scanned this ticket.
     */
    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    /**
     * Get the user who transferred this ticket from.
     */
    public function transferredFrom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_from');
    }

    /**
     * Get the user who this ticket was transferred to.
     */
    public function transferredTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_to');
    }

    /**
     * Check if ticket is valid for use.
     */
    public function isValid(): bool
    {
        if ($this->status !== 'valid') {
            return false;
        }

        $now = now();
        
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    /**
     * Mark ticket as used.
     */
    public function markAsUsed(string $userId, ?string $entryGate = null, ?string $deviceFingerprint = null): void
    {
        $this->update([
            'status' => 'used',
            'used_at' => now(),
            'used_by' => $userId,
            'entry_gate' => $entryGate,
            'device_fingerprint' => $deviceFingerprint,
        ]);
    }

    /**
     * Transfer ticket to another user.
     */
    public function transferTo(string $toUserId, string $fromUserId, ?string $reason = null): void
    {
        $this->update([
            'status' => 'transferred',
            'assigned_to' => $toUserId,
            'transferred_from' => $fromUserId,
            'transferred_to' => $toUserId,
            'transferred_at' => now(),
            'transfer_reason' => $reason,
        ]);
    }

    /**
     * Cancel ticket.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Generate QR code data.
     */
    public function generateQrData(): string
    {
        return json_encode([
            'ticket_code' => $this->ticket_code,
            'event_id' => $this->event_id,
            'hash' => $this->ticket_hash,
        ]);
    }
}