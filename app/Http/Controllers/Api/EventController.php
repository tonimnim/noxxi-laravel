<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class EventController extends Controller
{
    use ApiResponse;

    /**
     * List all events with filters
     */
    public function index(Request $request)
    {
        $events = QueryBuilder::for(Event::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('city'),
                AllowedFilter::exact('category_id'),
                AllowedFilter::scope('upcoming'),
                AllowedFilter::scope('featured'),
                AllowedFilter::callback('date_after', function ($query, $value) {
                    $query->where('event_date', '>=', $value);
                }),
                AllowedFilter::callback('date_before', function ($query, $value) {
                    $query->where('event_date', '<=', $value);
                }),
                AllowedFilter::callback('price_min', function ($query, $value) {
                    $query->where('min_price', '>=', $value);
                }),
                AllowedFilter::callback('price_max', function ($query, $value) {
                    $query->where('max_price', '<=', $value);
                }),
                AllowedFilter::partial('title'),
            ])
            ->allowedIncludes(['organizer', 'category'])
            ->allowedSorts(['event_date', 'created_at', 'min_price', 'title'])
            ->defaultSort('-event_date')
            ->where('status', 'published')
            ->with(['organizer:id,business_name,business_logo_url'])
            ->select([
                'id',
                'title',
                'slug',
                'event_date',
                'end_date',
                'venue_name',
                'city',
                'min_price',
                'max_price',
                'currency',
                'cover_image_url',
                'featured',
                'capacity',
                'tickets_sold',
                'organizer_id',
                'category_id'
            ])
            ->paginate($request->per_page ?? 20);

        return $this->success([
            'events' => $events->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ]
        ]);
    }

    /**
     * Get single event details
     */
    public function show($id)
    {
        $event = Event::with([
            'organizer:id,business_name,business_email,business_logo_url,business_description',
            'category:id,name,slug'
        ])->find($id);

        if (!$event) {
            return $this->notFound('Event not found');
        }

        if ($event->status !== 'published' && 
            (!auth()->user() || auth()->user()->id !== $event->organizer->user_id)) {
            return $this->forbidden('This event is not available');
        }

        $event->incrementViewCount();

        return $this->success([
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'slug' => $event->slug,
                'description' => $event->description,
                'event_date' => $event->event_date,
                'end_date' => $event->end_date,
                'venue_name' => $event->venue_name,
                'venue_address' => $event->venue_address,
                'latitude' => $event->latitude,
                'longitude' => $event->longitude,
                'city' => $event->city,
                'capacity' => $event->capacity,
                'tickets_sold' => $event->tickets_sold,
                'available_tickets' => $event->available_tickets,
                'min_price' => $event->min_price,
                'max_price' => $event->max_price,
                'currency' => $event->currency,
                'ticket_types' => $event->ticket_types,
                'images' => $event->images,
                'cover_image_url' => $event->cover_image_url,
                'tags' => $event->tags,
                'status' => $event->status,
                'featured' => $event->featured,
                'age_restriction' => $event->age_restriction,
                'terms_conditions' => $event->terms_conditions,
                'refund_policy' => $event->refund_policy,
                'organizer' => $event->organizer,
                'category' => $event->category,
                'is_sold_out' => $event->isSoldOut(),
                'is_upcoming' => $event->isUpcoming(),
            ]
        ]);
    }

    /**
     * Get upcoming events
     */
    public function upcoming(Request $request)
    {
        $events = Event::upcoming()
            ->with(['organizer:id,business_name,business_logo_url'])
            ->select([
                'id',
                'title',
                'slug',
                'event_date',
                'venue_name',
                'city',
                'min_price',
                'currency',
                'cover_image_url',
                'featured',
                'tickets_sold',
                'capacity',
                'organizer_id'
            ])
            ->paginate($request->per_page ?? 20);

        return $this->success([
            'events' => $events->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ]
        ]);
    }

    /**
     * Get featured events
     */
    public function featured(Request $request)
    {
        $events = Event::featured()
            ->upcoming()
            ->with(['organizer:id,business_name,business_logo_url'])
            ->select([
                'id',
                'title',
                'slug',
                'event_date',
                'venue_name',
                'city',
                'min_price',
                'currency',
                'cover_image_url',
                'featured',
                'featured_until',
                'tickets_sold',
                'capacity',
                'organizer_id'
            ])
            ->limit(10)
            ->get();

        return $this->success(['events' => $events]);
    }

    /**
     * Get event categories
     */
    public function categories()
    {
        $categories = EventCategory::where('is_active', true)
            ->select(['id', 'name', 'slug', 'icon_url', 'color_hex'])
            ->orderBy('display_order')
            ->get();

        return $this->success(['categories' => $categories]);
    }

    /**
     * Search events
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (!$query || strlen($query) < 2) {
            return $this->validationError(['q' => 'Search query must be at least 2 characters']);
        }

        $events = Event::where('status', 'published')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('venue_name', 'like', "%{$query}%")
                  ->orWhere('city', 'like', "%{$query}%");
            })
            ->with(['organizer:id,business_name'])
            ->select([
                'id',
                'title',
                'slug',
                'event_date',
                'venue_name',
                'city',
                'min_price',
                'currency',
                'cover_image_url',
                'organizer_id'
            ])
            ->limit(20)
            ->get();

        return $this->success(['events' => $events]);
    }
}
