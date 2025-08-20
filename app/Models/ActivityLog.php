<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'type',
        'action',
        'level',
        'title',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'ip_address',
        'user_agent',
        'icon',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Activity levels
    const LEVEL_CRITICAL = 'critical';
    const LEVEL_IMPORTANT = 'important';
    const LEVEL_INFO = 'info';
    const LEVEL_DEBUG = 'debug';

    // Activity types
    const TYPE_PAYMENT = 'payment';
    const TYPE_ORGANIZER = 'organizer';
    const TYPE_EVENT = 'event';
    const TYPE_USER = 'user';
    const TYPE_SYSTEM = 'system';

    // Common actions
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_DELETED = 'deleted';
    const ACTION_APPROVED = 'approved';
    const ACTION_REJECTED = 'rejected';
    const ACTION_FAILED = 'failed';
    const ACTION_COMPLETED = 'completed';
    const ACTION_REGISTERED = 'registered';
    const ACTION_LOGIN = 'login';

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeImportant($query)
    {
        return $query->whereIn('level', [self::LEVEL_CRITICAL, self::LEVEL_IMPORTANT]);
    }

    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getFormattedTimeAttribute(): string
    {
        $diff = $this->created_at->diffInMinutes(now());
        
        if ($diff < 1) {
            return 'Just now';
        } elseif ($diff < 60) {
            return $diff . ' min ago';
        } elseif ($diff < 1440) {
            return $this->created_at->diffInHours(now()) . ' hours ago';
        } else {
            return $this->created_at->format('M d, H:i');
        }
    }

    public function getIconColorAttribute(): string
    {
        return match($this->type) {
            self::TYPE_PAYMENT => $this->action === self::ACTION_FAILED ? 'danger' : 'success',
            self::TYPE_ORGANIZER => 'warning',
            self::TYPE_EVENT => 'info',
            self::TYPE_USER => 'primary',
            self::TYPE_SYSTEM => 'gray',
            default => 'gray',
        };
    }

    public function getIconNameAttribute(): string
    {
        return match($this->type) {
            self::TYPE_PAYMENT => 'heroicon-o-credit-card',
            self::TYPE_ORGANIZER => 'heroicon-o-building-office',
            self::TYPE_EVENT => 'heroicon-o-calendar',
            self::TYPE_USER => 'heroicon-o-user',
            self::TYPE_SYSTEM => 'heroicon-o-cog',
            default => 'heroicon-o-information-circle',
        };
    }
}