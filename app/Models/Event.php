<?php

namespace App\Models;

use App\Services\CloudinaryService;
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
        'listing_type',
        'ticket_types',
        'ticket_sales_config',
        'capacity',
        'tickets_sold',
        'min_price',
        'max_price',
        'currency',
        'organizer_fee',
        'images',
        'cover_image_url',
        'media',
        'tags',
        'status',
        'draft_data',
        'draft_saved_at',
        'featured',
        'featured_until',
        'requires_approval',
        'listing_settings',
        'age_restriction',
        'terms_conditions',
        'policies',
        'offline_mode_data',
        'qr_secret_key',
        'gates_config',
        'check_in_enabled',
        'check_in_opens_at',
        'check_in_closes_at',
        'allow_immediate_check_in',
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
            'check_in_opens_at' => 'datetime',
            'check_in_closes_at' => 'datetime',
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
            'check_in_enabled' => 'boolean',
            'allow_immediate_check_in' => 'boolean',
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
     * Get full cover image URL
     */
    public function getCoverImageUrlAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        // If already a full URL, return as is
        if (str_starts_with($value, 'http')) {
            return $value;
        }
        
        // Fix double /storage/ issue
        if (str_starts_with($value, '/storage/')) {
            return asset($value);
        }

        // Return full URL with storage path
        return asset("storage/{$value}");
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
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->where(function ($sub) {
                    // Services (no event_date) are always upcoming unless they have expired end_date
                    $sub->where('listing_type', 'service')
                        ->whereNull('event_date')
                        ->where(function ($dateSub) {
                            $dateSub->whereNull('end_date')
                                ->orWhere('end_date', '>=', now());
                        });
                })->orWhere(function ($sub) {
                    // Events with end_date: show if end_date hasn't passed
                    $sub->where('listing_type', '!=', 'service')
                        ->whereNotNull('end_date')
                        ->where('end_date', '>=', now());
                })->orWhere(function ($sub) {
                    // Events without end_date: show if event_date hasn't passed
                    $sub->where('listing_type', '!=', 'service')
                        ->whereNull('end_date')
                        ->where('event_date', '>=', now());
                });
            })
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

    /**
     * Get featured image URL with Cloudinary transformation
     */
    public function getFeaturedImageUrl(string $transformation = 'event_card'): ?string
    {
        if (empty($this->media) || !isset($this->media[0])) {
            return $this->cover_image_url;
        }

        $firstImage = $this->media[0];
        
        // Check if it's a Cloudinary image
        if (isset($firstImage['cloudinary_id'])) {
            $cloudinary = new CloudinaryService();
            return $cloudinary->getTransformedUrl($firstImage['cloudinary_id'], $transformation);
        }
        
        // Return the URL from media array
        if (isset($firstImage['url'])) {
            $url = $firstImage['url'];
            // If it's a relative URL starting with /storage/, convert to full URL
            if (str_starts_with($url, '/storage/')) {
                return asset($url);
            }
            // If it's already a full URL, return it
            if (str_starts_with($url, 'http')) {
                return $url;
            }
            // Otherwise, assume it's a storage path
            return asset('storage/' . $url);
        }
        
        // Fallback to cover_image_url
        return $this->cover_image_url;
    }

    /**
     * Get all image URLs with responsive versions
     */
    public function getResponsiveImages(): array
    {
        if (empty($this->media)) {
            return [];
        }

        $cloudinary = new CloudinaryService();
        $images = [];

        foreach ($this->media as $media) {
            if (isset($media['cloudinary_id'])) {
                $images[] = $cloudinary->getResponsiveUrls($media['cloudinary_id']);
            } elseif (isset($media['url'])) {
                // Fallback for non-Cloudinary images
                $images[] = [
                    'mobile' => $media['url'],
                    'tablet' => $media['url'],
                    'desktop' => $media['url'],
                    'original' => $media['url'],
                ];
            }
        }

        return $images;
    }
}
