<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Support\Facades\Cache;

class ListingController extends Controller
{
    /**
     * Display listing (event) details page
     * Supports both UUID and slug for SEO-friendly URLs
     */
    public function show($identifier)
    {
        // Try to find by slug first (SEO-friendly), then by ID
        $event = Cache::remember("event_{$identifier}", 300, function () use ($identifier) {
            // First try to find by slug
            $event = Event::with([
                'organizer:id,business_name,business_logo_url,business_description,is_verified',
                'category:id,name,slug,parent_id',
            ])
                ->where('slug', $identifier)
                ->where('status', 'published')
                ->first();

            // If not found by slug and identifier is a valid UUID, try by ID
            if (! $event && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $identifier)) {
                $event = Event::with([
                    'organizer:id,business_name,business_logo_url,business_description,is_verified',
                    'category:id,name,slug,parent_id',
                ])
                    ->where('id', $identifier)
                    ->where('status', 'published')
                    ->first();
            }

            return $event;
        });

        if (! $event) {
            abort(404, 'Listing not found');
        }

        // Increment view count (not cached)
        $event->increment('view_count');

        // Pass event data to Vue component
        return view('listings.show', [
            'event' => $event,
            'eventData' => $this->formatEventForVue($event),
        ]);
    }

    /**
     * Format event data for Vue component consumption
     */
    protected function formatEventForVue($event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'slug' => $event->slug,
            'description' => $event->description,
            'category' => $event->category,
            'organizer' => [
                'id' => $event->organizer->id,
                'name' => $event->organizer->business_name,
                'logo' => $event->organizer->business_logo_url,
                'description' => $event->organizer->business_description,
                'is_verified' => $event->organizer->is_verified,
            ],
            'venue' => [
                'name' => $event->venue_name,
                'address' => $event->venue_address,
                'city' => $event->city,
                'latitude' => $event->latitude,
                'longitude' => $event->longitude,
            ],
            'dates' => [
                'event_date' => $event->event_date->toIso8601String(),
                'end_date' => $event->end_date?->toIso8601String(),
                'formatted_date' => $event->event_date->format('D, M j, Y'),
                'formatted_time' => $event->event_date->format('g:i A'),
            ],
            'pricing' => [
                'min_price' => $event->min_price,
                'max_price' => $event->max_price,
                'currency' => $event->currency,
                'ticket_types' => $event->ticket_types,
            ],
            'availability' => [
                'capacity' => $event->capacity,
                'tickets_sold' => $event->tickets_sold,
                'available_tickets' => $event->available_tickets,
                'is_sold_out' => $event->isSoldOut(),
            ],
            'media' => [
                'cover_image' => $event->cover_image_url,
                'images' => $event->images,
                'video_url' => $event->video_url,
            ],
            'policies' => [
                'age_restriction' => $event->age_restriction,
                'terms_conditions' => $event->terms_conditions,
            ],
            'tags' => $event->tags,
            'is_featured' => $event->featured,
            'is_upcoming' => $event->isUpcoming(),
        ];
    }
}
