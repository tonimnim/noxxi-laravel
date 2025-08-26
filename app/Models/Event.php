<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
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
        'organizer_id',
        'title',
        'slug',
        'description',
        'category_id',
        'category_metadata',
        'venue_name',
        'venue_address',
        'latitude',
        'longitude',
        'city',
        'city_id',
        'event_date',
        'end_date',
        'ticket_types',
        'ticket_sales_config',
        'capacity',
        'tickets_sold',
        'min_price',
        'max_price',
        'currency',
        'organizer_fee',
        'platform_fee',
        'images',
        'cover_image_url',
        'media',
        'tags',
        'status',
        'commission_rate',
        'commission_type',
        'draft_data',
        'draft_saved_at',
        'featured',
        'featured_until',
        'requires_approval',
        'listing_settings',
        'age_restriction',
        'terms_conditions',
        'refund_policy',
        'policies',
        'offline_mode_data',
        'qr_secret_key',
        'gates_config',
        'seo_keywords',
        'marketing',
        'view_count',
        'share_count',
        'analytics',
        'published_at',
        'first_published_at',
        'last_modified_at',
        'modified_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'datetime',
            'end_date' => 'datetime',
            'published_at' => 'datetime',
            'featured_until' => 'datetime',
            'first_published_at' => 'datetime',
            'last_modified_at' => 'datetime',
            'draft_saved_at' => 'datetime',
            'ticket_types' => 'array',
            'ticket_sales_config' => 'array',
            'category_metadata' => 'array',
            'images' => 'array',
            'media' => 'array',
            'tags' => 'array',
            'seo_keywords' => 'array',
            'marketing' => 'array',
            'policies' => 'array',
            'listing_settings' => 'array',
            'draft_data' => 'array',
            'offline_mode_data' => 'array',
            'gates_config' => 'array',
            'analytics' => 'array',
            'featured' => 'boolean',
            'requires_approval' => 'boolean',
            'tickets_sold' => 'integer',
            'capacity' => 'integer',
            'view_count' => 'integer',
            'share_count' => 'integer',
            'age_restriction' => 'integer',
            'min_price' => 'decimal:2',
            'max_price' => 'decimal:2',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title);

                // Ensure unique slug
                $count = static::where('slug', 'like', $event->slug.'%')->count();
                if ($count > 0) {
                    $event->slug = $event->slug.'-'.($count + 1);
                }
            }

            // Set default currency from organizer
            if (empty($event->currency) && $event->organizer) {
                $event->currency = $event->organizer->default_currency ?? config('currencies.default', 'USD');
            }
        });

        static::updating(function ($event) {
            // Update published_at when status changes to published
            if ($event->isDirty('status') && $event->status === 'published' && ! $event->published_at) {
                $event->published_at = now();
            }
        });
    }

    /**
     * Get the organizer that owns the event.
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * Get the category of the event.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    /**
     * Get the city of the event.
     */
    public function cityRelation(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    /**
     * Get the bookings for the event.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get paid bookings for revenue calculation
     */
    public function paidBookings(): HasMany
    {
        return $this->hasMany(Booking::class)->where('payment_status', 'paid');
    }

    /**
     * Get the tickets for the event.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Check if event is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if event is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->event_date->isFuture();
    }

    /**
     * Check if event is past.
     */
    public function isPast(): bool
    {
        return $this->event_date->isPast();
    }

    /**
     * Check if event is happening now.
     */
    public function isHappeningNow(): bool
    {
        $now = now();

        return $this->event_date->lte($now) &&
               ($this->end_date ? $this->end_date->gte($now) : $this->event_date->addDay()->gte($now));
    }

    /**
     * Check if event is sold out.
     */
    public function isSoldOut(): bool
    {
        return $this->tickets_sold >= $this->capacity;
    }

    /**
     * Get available tickets count.
     */
    public function getAvailableTicketsAttribute(): int
    {
        return max(0, $this->capacity - $this->tickets_sold);
    }

    /**
     * Get total revenue attribute
     */
    public function getTotalRevenueAttribute(): float
    {
        if ($this->relationLoaded('paidBookings')) {
            return $this->paidBookings->sum('total_amount');
        }

        return $this->paidBookings()->sum('total_amount');
    }

    /**
     * Get ticket type by name.
     */
    public function getTicketType(string $typeName): ?array
    {
        if (! is_array($this->ticket_types)) {
            return null;
        }

        foreach ($this->ticket_types as $type) {
            if ($type['name'] === $typeName) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Update ticket sold count.
     */
    public function incrementTicketsSold(int $count = 1): void
    {
        $this->increment('tickets_sold', $count);

        // Update organizer stats
        if ($this->organizer) {
            $this->organizer->increment('total_tickets_sold', $count);
        }
    }

    /**
     * Increment view count.
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Increment share count.
     */
    public function incrementShareCount(): void
    {
        $this->increment('share_count');
    }

    /**
     * Scope for published events.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope for upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>', now())
            ->where('status', 'published')
            ->orderBy('event_date');
    }

    /**
     * Scope for featured events.
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true)
            ->where(function ($q) {
                $q->whereNull('featured_until')
                    ->orWhere('featured_until', '>', now());
            });
    }

    /**
     * Scope for events by city.
     */
    public function scopeInCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    /**
     * Scope for events by category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}
