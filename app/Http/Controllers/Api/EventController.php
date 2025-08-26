<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class EventController extends Controller
{
    use ApiResponse;

    /**
     * List all events with filters
     */
    public function index(Request $request)
    {
        $events = QueryBuilder::for(Event::class)
            ->allowedFilters($this->getAllowedFilters())
            ->allowedIncludes(['organizer', 'category'])
            ->allowedSorts(['event_date', 'created_at', 'min_price', 'title'])
            ->defaultSort('-event_date')
            ->where('status', 'published')
            ->with([
                'organizer:id,business_name,business_logo_url',
                'category:id,name,slug',
            ])
            ->select($this->getListFields())
            ->paginate($request->per_page ?? 20);

        return $this->success([
            'events' => $events->items(),
            'meta' => $this->getPaginationMeta($events),
        ]);
    }

    /**
     * Get single event details
     */
    public function show($id)
    {
        $event = Event::with([
            'organizer:id,business_name,business_email,business_logo_url,business_description',
            'category:id,name,slug',
        ])->find($id);

        if (! $event) {
            return $this->notFound('Event not found');
        }

        if (! $this->canViewEvent($event)) {
            return $this->forbidden('This event is not available');
        }

        $event->incrementViewCount();

        return $this->success(['event' => $this->formatEventDetails($event)]);
    }

    /**
     * Get upcoming events
     */
    public function upcoming(Request $request)
    {
        $events = Event::upcoming()
            ->with(['organizer:id,business_name,business_logo_url'])
            ->select($this->getListFields())
            ->paginate($request->per_page ?? 20);

        return $this->success([
            'events' => $events->items(),
            'meta' => $this->getPaginationMeta($events),
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
            ->select(array_merge($this->getListFields(), ['featured_until']))
            ->limit(10)
            ->get();

        return $this->success(['events' => $events]);
    }

    /**
     * Get allowed filters for query builder
     */
    private function getAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('status'),
            AllowedFilter::exact('city'),
            AllowedFilter::exact('category_id'),
            AllowedFilter::callback('category', function ($query, $value) {
                $query->whereHas('category', function ($q) use ($value) {
                    $q->where('slug', $value)
                        ->orWhere('name', 'ILIKE', $value)
                        ->orWhereHas('parent', function ($parentQuery) use ($value) {
                            $parentQuery->where('slug', $value)
                                ->orWhere('name', 'ILIKE', $value);
                        });
                });
            }),
            AllowedFilter::scope('upcoming'),
            AllowedFilter::scope('featured'),
            AllowedFilter::callback('date_after', fn ($q, $v) => $q->where('event_date', '>=', $v)),
            AllowedFilter::callback('date_before', fn ($q, $v) => $q->where('event_date', '<=', $v)),
            AllowedFilter::callback('price_min', fn ($q, $v) => $q->where('min_price', '>=', $v)),
            AllowedFilter::callback('price_max', fn ($q, $v) => $q->where('max_price', '<=', $v)),
            AllowedFilter::partial('title'),
        ];
    }

    /**
     * Get fields for list views
     */
    private function getListFields(): array
    {
        return [
            'id', 'title', 'slug', 'event_date', 'end_date',
            'venue_name', 'city', 'min_price', 'max_price',
            'currency', 'cover_image_url', 'featured',
            'capacity', 'tickets_sold', 'organizer_id', 'category_id',
        ];
    }

    /**
     * Format event details for response
     */
    private function formatEventDetails($event): array
    {
        return [
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
        ];
    }

    /**
     * Get pagination metadata
     */
    private function getPaginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * Check if user can view event
     */
    private function canViewEvent($event): bool
    {
        if ($event->status === 'published') {
            return true;
        }

        $user = auth()->user();

        return $user && $user->id === $event->organizer->user_id;
    }

    /**
     * Get experiences events
     */
    public function experiences(Request $request)
    {
        $events = Event::query()
            ->whereHas('category', function ($query) {
                $query->where('slug', 'experiences');
            })
            ->where('status', 'published')
            ->where('event_date', '>', now())
            ->with(['organizer:id,business_name,business_logo_url'])
            ->select($this->getListFields())
            ->orderBy('event_date', 'asc')
            ->paginate($request->per_page ?? 12);

        return $this->success([
            'events' => $events->items(),
            'meta' => $this->getPaginationMeta($events),
        ]);
    }

    /**
     * Get sports events
     */
    public function sports(Request $request)
    {
        $events = Event::query()
            ->whereHas('category', function ($query) {
                $query->where('slug', 'sports');
            })
            ->where('status', 'published')
            ->where('event_date', '>', now())
            ->with(['organizer:id,business_name,business_logo_url'])
            ->select($this->getListFields())
            ->orderBy('event_date', 'asc')
            ->paginate($request->per_page ?? 12);

        return $this->success([
            'events' => $events->items(),
            'meta' => $this->getPaginationMeta($events),
        ]);
    }

    /**
     * Get cinema/movie events
     */
    public function cinema(Request $request)
    {
        $events = Event::query()
            ->whereHas('category', function ($query) {
                $query->where('slug', 'cinema');
            })
            ->where('status', 'published')
            ->where('event_date', '>', now())
            ->with(['organizer:id,business_name,business_logo_url'])
            ->select($this->getListFields())
            ->orderBy('event_date', 'asc')
            ->paginate($request->per_page ?? 12);

        return $this->success([
            'events' => $events->items(),
            'meta' => $this->getPaginationMeta($events),
        ]);
    }
}
