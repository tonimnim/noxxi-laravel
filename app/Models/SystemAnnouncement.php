<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SystemAnnouncement extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'type',
        'title',
        'message',
        'priority',
        'is_active',
        'scheduled_for',
        'expires_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'scheduled_for' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Type constants
    const TYPE_MAINTENANCE = 'maintenance';
    const TYPE_UPDATE = 'update';
    const TYPE_ALERT = 'alert';
    const TYPE_INFO = 'info';

    // Priority constants
    const PRIORITY_CRITICAL = 'critical';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_LOW = 'low';

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes for optimized queries
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        $now = now();
        return $query->active()
            ->where(function ($q) use ($now) {
                $q->whereNull('scheduled_for')
                  ->orWhere('scheduled_for', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', $now);
            });
    }

    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderByRaw("
            CASE priority
                WHEN 'critical' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END
        ");
    }

    // Accessors
    public function getIconAttribute(): string
    {
        return match($this->type) {
            self::TYPE_MAINTENANCE => 'wrench-screwdriver',
            self::TYPE_UPDATE => 'arrow-path',
            self::TYPE_ALERT => 'exclamation-triangle',
            self::TYPE_INFO => 'information-circle',
            default => 'megaphone',
        };
    }

    public function getColorAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_CRITICAL => 'red',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_MEDIUM => 'yellow',
            self::PRIORITY_LOW => 'blue',
            default => 'gray',
        };
    }

    public function getFormattedTimeAttribute(): string
    {
        if ($this->scheduled_for && $this->scheduled_for->isFuture()) {
            return 'Scheduled for ' . $this->scheduled_for->format('M d, H:i');
        }
        
        return $this->created_at->diffForHumans();
    }
}