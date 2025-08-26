<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Get trending listings for homepage
     */
    public function trending()
    {
        $events = Event::query()
            ->where('status', 'published')
            ->where('event_date', '>', now())
            ->orderByDesc('view_count')
            ->orderByDesc('created_at')
            ->with(['category:id,name,slug', 'organizer:id,business_name'])
            ->limit(8)
            ->select([
                'id',
                'title',
                'cover_image_url',
                'min_price',
                'currency',
                'event_date',
                'city',
                'view_count',
                'category_id',
                'organizer_id',
                'venue_name',
            ])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $events,
        ]);
    }

    /**
     * Get featured listings for homepage
     */
    public function featured(Request $request)
    {
        $query = Event::query()
            ->where('status', 'published')
            ->where('event_date', '>', now())
            ->where('featured', true);

        // Filter by category if provided
        if ($request->has('category') && $request->category !== 'all') {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        $events = $query->orderBy('event_date', 'asc')
            ->with(['category:id,name,slug', 'organizer:id,business_name'])
            ->limit(12)
            ->select([
                'id',
                'title',
                'cover_image_url',
                'min_price',
                'currency',
                'event_date',
                'city',
                'venue_name',
                'category_id',
                'organizer_id',
            ])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $events,
        ]);
    }
}
