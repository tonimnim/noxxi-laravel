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
            ->whereNotNull('cover_image_url')
            ->orderByDesc('view_count')
            ->orderByDesc('created_at')
            ->limit(8)
            ->select([
                'id', 
                'title', 
                'cover_image_url', 
                'min_price', 
                'currency', 
                'event_date', 
                'city',
                'view_count'
            ])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $events
        ]);
    }
}