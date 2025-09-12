<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventSearchController extends Controller
{
    use ApiResponse;

    /**
     * Smart search for events
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $location = $request->get('location');
        $date = $request->get('date');
        $category = $request->get('category');

        // Allow search with just filters (no query required)
        if (! $query && ! $location && ! $date && ! $category) {
            return $this->success(['events' => []]);
        }

        // Search term aliases
        $searchAliases = $this->getSearchAliases();

        // Check if search term matches an alias
        $mappedCategory = $this->findMappedCategory($query, $searchAliases);

        // Find direct category match
        $categoryMatch = $mappedCategory ?: EventCategory::where('name', 'ILIKE', "%{$query}%")
            ->orWhere('slug', 'ILIKE', "%{$query}%")
            ->first();

        $events = Event::where('status', 'published')
            ->when($query, function ($q) use ($query, $categoryMatch) {
                $q->where(function ($subQuery) use ($query, $categoryMatch) {
                    // Search in event fields
                    $subQuery->where('title', 'ILIKE', "%{$query}%")
                        ->orWhere('description', 'ILIKE', "%{$query}%")
                        ->orWhere('venue_name', 'ILIKE', "%{$query}%")
                        ->orWhere('city', 'ILIKE', "%{$query}%")
                        ->orWhereJsonContains('tags', $query);

                    // Search in organizer name
                    $subQuery->orWhereHas('organizer', function ($orgQuery) use ($query) {
                        $orgQuery->where('business_name', 'ILIKE', "%{$query}%");
                    });

                    // Include category matches
                    if ($categoryMatch) {
                        $subQuery->orWhere('category_id', $categoryMatch->id);
                        if ($categoryMatch->children()->exists()) {
                            $childIds = $categoryMatch->children()->pluck('id');
                            $subQuery->orWhereIn('category_id', $childIds);
                        }
                    }

                    // Search in category names
                    $subQuery->orWhereHas('category', function ($catQuery) use ($query) {
                        $catQuery->where('name', 'ILIKE', "%{$query}%")
                            ->orWhere('slug', 'ILIKE', "%{$query}%");
                    });
                });
            })
            ->when($location, function ($q) use ($location) {
                // Search by location (city name or country via cities table)
                $q->where(function ($subQuery) use ($location) {
                    $subQuery->where('city', 'ILIKE', "%{$location}%")
                        ->orWhereHas('cityRelation', function ($cityQuery) use ($location) {
                            $cityQuery->where('country', 'ILIKE', "%{$location}%");
                        });
                });
            })
            ->when($date, function ($q) use ($date) {
                // Filter by date
                $q->whereDate('event_date', '>=', $date);
            })
            ->when($category && $category !== 'all', function ($q) use ($category) {
                // Filter by category slug
                $q->whereHas('category', function ($catQuery) use ($category) {
                    $catQuery->where('slug', $category);
                });
            })
            ->with(['organizer:id,business_name', 'category'])
            ->select([
                'id', 'title', 'slug', 'event_date', 'venue_name',
                'city', 'min_price', 'currency', 'cover_image_url', 'organizer_id',
            ])
            ->limit(20)
            ->get();

        return $this->success(['events' => $events]);
    }

    /**
     * Get search suggestions
     */
    public function suggestions(Request $request)
    {
        $query = $request->get('q');

        if (! $query || strlen($query) < 2) {
            return $this->success(['suggestions' => []]);
        }

        $suggestions = [];
        $seen = [];
        $lowerQuery = strtolower($query);

        // Get brand mappings
        $brandMappings = $this->getBrandMappings();

        // Check for brand/term matches
        foreach ($brandMappings as $brand => $info) {
            if (stripos($lowerQuery, $brand) !== false ||
                levenshtein($lowerQuery, $brand) <= 2) {
                $key = 'cat_'.$info['category'];
                if (! isset($seen[$key])) {
                    $suggestions[] = [
                        'type' => 'category_suggestion',
                        'text' => "Browse all {$info['type']}",
                        'category' => $info['category'],
                        'action' => 'filter_category',
                        'priority' => 1,
                    ];
                    $seen[$key] = true;
                }
            }
        }

        // Add event matches
        $this->addEventSuggestions($query, $suggestions);

        // Add category matches
        $this->addCategorySuggestions($query, $suggestions, $seen);

        // Add city matches
        $this->addCitySuggestions($query, $suggestions);

        // Add organizer matches
        $this->addOrganizerSuggestions($query, $suggestions);

        // Sort and limit
        usort($suggestions, fn ($a, $b) => $a['priority'] <=> $b['priority']);
        $suggestions = array_map(function ($item) {
            unset($item['priority']);

            return $item;
        }, array_slice($suggestions, 0, 10));

        return $this->success(['suggestions' => $suggestions]);
    }

    /**
     * Get search aliases mapping
     */
    private function getSearchAliases(): array
    {
        return [
            'movie' => 'cinema',
            'movies' => 'cinema',
            'film' => 'cinema',
            'match' => 'sports',
            'game' => 'sports',
            'boxing' => 'combat',
            'mma' => 'combat',
            'wrestling' => 'combat',
            'martial arts' => 'combat',
            'ufc' => 'combat',
            'billiards' => 'pool',
            'snooker' => 'pool',
            '8-ball' => 'pool',
            '9-ball' => 'pool',
            'f1' => 'motorsports',
            'formula 1' => 'motorsports',
            'racing' => 'motorsports',
            'rally' => 'motorsports',
            'concert' => 'concerts',
            'safari' => 'adventure',
            'party' => 'nightlife',
            'club' => 'nightlife',
            'nightclub' => 'nightlife',
        ];
    }

    /**
     * Get brand mappings
     */
    private function getBrandMappings(): array
    {
        return [
            'netflix' => ['category' => 'cinema', 'type' => 'Cinema'],
            'imax' => ['category' => 'cinema', 'type' => 'Cinema'],
            'premier league' => ['category' => 'football', 'type' => 'Football'],
            'festival' => ['category' => 'festivals', 'type' => 'Festivals'],
            'concert' => ['category' => 'concerts', 'type' => 'Concerts'],
            'ufc' => ['category' => 'combat', 'type' => 'Combat Sports'],
            'bellator' => ['category' => 'combat', 'type' => 'Combat Sports'],
            'wwe' => ['category' => 'combat', 'type' => 'Combat Sports'],
            'formula 1' => ['category' => 'motorsports', 'type' => 'Motorsports'],
            'nascar' => ['category' => 'motorsports', 'type' => 'Motorsports'],
        ];
    }

    /**
     * Find mapped category from aliases
     */
    private function findMappedCategory($query, $aliases)
    {
        $lowerQuery = strtolower($query);
        foreach ($aliases as $alias => $categorySlug) {
            if (stripos($lowerQuery, $alias) !== false) {
                return EventCategory::where('slug', $categorySlug)->first();
            }
        }

        return null;
    }

    /**
     * Add event suggestions
     */
    private function addEventSuggestions($query, &$suggestions)
    {
        $events = Event::where('status', 'published')
            ->where('title', 'ILIKE', "%{$query}%")
            ->select('id', 'title', 'event_date', 'city')
            ->limit(5)
            ->get();

        foreach ($events as $event) {
            $dateInfo = $event->event_date ? ' - '.date('M d', strtotime($event->event_date)) : '';
            $cityInfo = $event->city ? ' in '.$event->city : '';
            $suggestions[] = [
                'type' => 'event',
                'text' => $event->title,
                'subtitle' => trim($dateInfo.$cityInfo, ' -'),
                'event_id' => $event->id,
                'action' => 'view_event',
                'priority' => 2,
            ];
        }
    }

    /**
     * Add category suggestions
     */
    private function addCategorySuggestions($query, &$suggestions, &$seen)
    {
        $categories = EventCategory::where(function ($q) use ($query) {
            $q->where('name', 'ILIKE', "%{$query}%")
                ->orWhere('slug', 'ILIKE', "%{$query}%");
        })
            ->where('is_active', true)
            ->limit(4)
            ->get();

        foreach ($categories as $category) {
            $key = 'cat_id_'.$category->id;
            if (! isset($seen[$key])) {
                $parentInfo = $category->parent_id ? ' (in '.optional($category->parent)->name.')' : '';
                $suggestions[] = [
                    'type' => 'category',
                    'text' => $category->name.$parentInfo,
                    'category_id' => $category->id,
                    'slug' => $category->slug,
                    'action' => 'filter_category',
                    'priority' => 3,
                ];
                $seen[$key] = true;
            }
        }
    }

    /**
     * Add city suggestions
     */
    private function addCitySuggestions($query, &$suggestions)
    {
        $cities = Event::where('status', 'published')
            ->where('city', 'ILIKE', "%{$query}%")
            ->select('city', DB::raw('COUNT(*) as event_count'))
            ->groupBy('city')
            ->orderBy('event_count', 'desc')
            ->limit(3)
            ->get();

        foreach ($cities as $city) {
            $suggestions[] = [
                'type' => 'location',
                'text' => $city->city,
                'subtitle' => $city->event_count.' upcoming events',
                'action' => 'filter_city',
                'priority' => 4,
            ];
        }
    }

    /**
     * Add organizer suggestions
     */
    private function addOrganizerSuggestions($query, &$suggestions)
    {
        $organizers = DB::table('organizers as o')
            ->leftJoin('events as e', function ($join) {
                $join->on('e.organizer_id', '=', 'o.id')
                    ->where('e.status', '=', 'published');
            })
            ->where('o.is_active', true)
            ->where('o.business_name', 'ILIKE', "%{$query}%")
            ->select('o.id', 'o.business_name', DB::raw('COUNT(e.id) as event_count'))
            ->groupBy('o.id', 'o.business_name')
            ->havingRaw('COUNT(e.id) > 0')
            ->orderBy('event_count', 'desc')
            ->limit(2)
            ->get();

        foreach ($organizers as $organizer) {
            $suggestions[] = [
                'type' => 'organizer',
                'text' => $organizer->business_name,
                'subtitle' => $organizer->event_count.' events',
                'organizer_id' => $organizer->id,
                'action' => 'filter_organizer',
                'priority' => 5,
            ];
        }
    }
}
