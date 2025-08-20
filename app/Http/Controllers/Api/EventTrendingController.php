<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventTrendingController extends Controller  
{
    use ApiResponse;

    /**
     * Get trending events with category filtering
     */
    public function trending(Request $request)
    {
        // Build cache key
        $categoryKey = $request->category ? '_cat_' . $request->category : '';
        $cityKey = $request->city ? '_city_' . str_replace(' ', '_', $request->city) : '';
        $cacheKey = 'trending_events' . $categoryKey . $cityKey;
        
        $events = cache()->remember($cacheKey, 3600, function () use ($request) {
            $query = $this->buildTrendingQuery($request);
            
            return $query->orderBy('trending_score', 'desc')
                ->orderBy('event_date', 'asc')
                ->limit(20)
                ->get();
        });
        
        // Process events
        $events = $this->processEventData($events);
        
        return $this->success([
            'events' => $events,
            'meta' => [
                'total' => $events->count(),
                'cache_expires_in' => 3600,
                'filters' => [
                    'category' => $request->category,
                    'city' => $request->city
                ]
            ]
        ]);
    }

    /**
     * Get similar events
     */
    public function similar($id, Request $request)
    {
        $event = Event::find($id);
        
        if (!$event) {
            return $this->notFound('Event not found');
        }
        
        // Get similar events from different sources
        $sameCategoryEvents = $this->getSameCategoryEvents($event);
        $sameCityEvents = $this->getSameCityEvents($event);
        $sameOrganizerEvents = $this->getSameOrganizerEvents($event);
        
        // Combine and deduplicate
        $similarEvents = collect()
            ->merge($sameCategoryEvents)
            ->merge($sameCityEvents)
            ->merge($sameOrganizerEvents)
            ->unique('id')
            ->take(10)
            ->values();
        
        // Add similarity reasons
        $similarEvents = $this->addSimilarityReasons($similarEvents, $event);
        
        return $this->success([
            'events' => $similarEvents,
            'meta' => [
                'total' => $similarEvents->count(),
                'base_event' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'category' => $event->category->name ?? null
                ]
            ]
        ]);
    }

    /**
     * Build trending query with filters
     */
    private function buildTrendingQuery($request)
    {
        $query = Event::where('status', 'published')
            ->where('event_date', '>=', now())
            ->with(['organizer:id,business_name,business_logo_url', 'category:id,name,slug']);
        
        // Apply category filter
        if ($request->category) {
            $this->applyCategoryFilter($query, $request->category);
        }
        
        // Apply city filter
        if ($request->city) {
            $query->where('city', 'ILIKE', $request->city);
        }
        
        // Select with trending score calculation
        $query->select([
            'id', 'title', 'slug', 'event_date', 'end_date',
            'venue_name', 'city', 'min_price', 'max_price',
            'currency', 'cover_image_url', 'featured',
            'capacity', 'tickets_sold', 'organizer_id',
            'category_id', 'view_count',
            DB::raw($this->getTrendingScoreSQL())
        ]);
        
        return $query;
    }

    /**
     * Apply category filter to query
     */
    private function applyCategoryFilter($query, $categorySlug)
    {
        $query->where(function($q) use ($categorySlug) {
            $category = EventCategory::where('slug', $categorySlug)->first();
            
            if ($category) {
                if ($category->parent_id === null) {
                    // Parent category - get all children
                    $childIds = EventCategory::where('parent_id', $category->id)
                        ->pluck('id')
                        ->toArray();
                    $childIds[] = $category->id;
                    $q->whereIn('category_id', $childIds);
                } else {
                    // Child category - exact match
                    $q->where('category_id', $category->id);
                }
            } else {
                // Fallback to name matching
                $q->whereHas('category', function($catQuery) use ($categorySlug) {
                    $catQuery->where('name', 'ILIKE', $categorySlug)
                             ->orWhere('slug', 'ILIKE', $categorySlug);
                });
            }
        });
    }

    /**
     * Get SQL for trending score calculation
     */
    private function getTrendingScoreSQL(): string
    {
        return '(
            (COALESCE(view_count, 0) * 0.3) + 
            (COALESCE(tickets_sold, 0) * 0.5) + 
            (CASE 
                WHEN featured = true THEN 100
                ELSE 0
            END) +
            (CASE 
                WHEN event_date BETWEEN NOW() AND NOW() + INTERVAL \'7 days\' THEN 50
                WHEN event_date BETWEEN NOW() AND NOW() + INTERVAL \'14 days\' THEN 30
                WHEN event_date BETWEEN NOW() AND NOW() + INTERVAL \'30 days\' THEN 10
                ELSE 0
            END)
        ) as trending_score';
    }

    /**
     * Process event data for response
     */
    private function processEventData($events)
    {
        return $events->map(function ($event) {
            $soldPercentage = $event->capacity > 0 
                ? ($event->tickets_sold / $event->capacity) * 100 
                : 0;
            
            $event->is_selling_fast = $soldPercentage > 70;
            $event->sold_percentage = round($soldPercentage);
            
            // Remove internal fields
            unset($event->trending_score);
            
            return $event;
        });
    }

    /**
     * Get events from same category
     */
    private function getSameCategoryEvents($event)
    {
        return Event::where('status', 'published')
            ->where('event_date', '>=', now())
            ->where('id', '!=', $event->id)
            ->where('category_id', $event->category_id)
            ->with(['organizer:id,business_name,business_logo_url', 'category:id,name,slug'])
            ->select($this->getSimilarEventFields())
            ->limit(6)
            ->get();
    }

    /**
     * Get events from same city
     */
    private function getSameCityEvents($event)
    {
        return Event::where('status', 'published')
            ->where('event_date', '>=', now())
            ->where('id', '!=', $event->id)
            ->where('city', $event->city)
            ->where('category_id', '!=', $event->category_id)
            ->with(['organizer:id,business_name,business_logo_url', 'category:id,name,slug'])
            ->select($this->getSimilarEventFields())
            ->limit(4)
            ->get();
    }

    /**
     * Get events from same organizer
     */
    private function getSameOrganizerEvents($event)
    {
        return Event::where('status', 'published')
            ->where('event_date', '>=', now())
            ->where('id', '!=', $event->id)
            ->where('organizer_id', $event->organizer_id)
            ->with(['organizer:id,business_name,business_logo_url', 'category:id,name,slug'])
            ->select($this->getSimilarEventFields())
            ->limit(3)
            ->get();
    }

    /**
     * Get field list for similar events
     */
    private function getSimilarEventFields(): array
    {
        return [
            'id', 'title', 'slug', 'event_date', 'venue_name',
            'city', 'min_price', 'currency', 'cover_image_url',
            'organizer_id', 'category_id', 'tickets_sold', 'capacity'
        ];
    }

    /**
     * Add similarity reasons to events
     */
    private function addSimilarityReasons($events, $baseEvent)
    {
        return $events->map(function ($item) use ($baseEvent) {
            $reasons = [];
            
            if ($item->category_id === $baseEvent->category_id) {
                $reasons[] = 'same_category';
            }
            if ($item->city === $baseEvent->city) {
                $reasons[] = 'same_city';
            }
            if ($item->organizer_id === $baseEvent->organizer_id) {
                $reasons[] = 'same_organizer';
            }
            
            $item->similarity_reasons = $reasons;
            
            return $item;
        });
    }
}