<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Get trending listings for homepage
     */
    public function trending(Request $request)
    {
        $events = Event::query()
            ->where('status', 'published')
            ->where(function ($q) {
                $q->where(function ($sub) {
                    // Events with end_date: show if end_date hasn't passed
                    $sub->whereNotNull('end_date')
                        ->where('end_date', '>=', now());
                })->orWhere(function ($sub) {
                    // Events without end_date: show if event_date hasn't passed
                    $sub->whereNull('end_date')
                        ->where('event_date', '>=', now());
                })->orWhere(function ($sub) {
                    // Services without dates
                    $sub->where('listing_type', 'service')
                        ->whereNull('event_date');
                });
            })
            ->orderByDesc('view_count')
            ->orderByDesc('created_at')
            ->with(['category:id,name,slug', 'organizer:id,business_name'])
            ->limit(8)
            ->select([
                'id',
                'title',
                'cover_image_url',
                'media',
                'min_price',
                'currency',
                'event_date',
                'city',
                'view_count',
                'category_id',
                'organizer_id',
                'venue_name',
                'venue_address',
            ])
            ->get();

        // Apply image transformations if requested
        if ($request->has('image_width') || $request->has('image_height')) {
            $events = $this->transformEventImages($events, $request);
        } else {
            $events = $events->map(function ($event) {
                // Use Cloudinary thumbnail if available, otherwise fallback to cover_image_url
                $event->cover_image_url = $event->getFeaturedImageUrl('event_thumbnail') ?? $event->cover_image_url;
                return $event;
            });
        }

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
            ->where(function ($q) {
                $q->where(function ($sub) {
                    // Events with end_date: show if end_date hasn't passed
                    $sub->whereNotNull('end_date')
                        ->where('end_date', '>=', now());
                })->orWhere(function ($sub) {
                    // Events without end_date: show if event_date hasn't passed
                    $sub->whereNull('end_date')
                        ->where('event_date', '>=', now());
                });
            })
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
                'slug',
                'description',
                'cover_image_url',
                'media',
                'min_price',
                'currency',
                'event_date',
                'city',
                'venue_name',
                'venue_address',
                'category_id',
                'organizer_id',
            ])
            ->get();

        // Apply image transformations if requested
        if ($request->has('image_width') || $request->has('image_height')) {
            $events = $this->transformEventImages($events, $request);
        } else {
            $events = $events->map(function ($event) {
                // Use Cloudinary thumbnail if available, otherwise fallback to cover_image_url
                $event->cover_image_url = $event->getFeaturedImageUrl('event_thumbnail') ?? $event->cover_image_url;
                return $event;
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $events,
        ]);
    }

    /**
     * Transform event images using Cloudinary
     */
    private function transformEventImages($events, Request $request)
    {
        $width = $request->input('image_width');
        $height = $request->input('image_height');
        $crop = $request->input('image_crop', 'fill');
        $gravity = $request->input('g', 'auto');

        // If no dimensions specified, return original
        if (!$width && !$height) {
            return $events;
        }

        $cloudinary = new CloudinaryService();

        return $events->map(function ($event) use ($cloudinary, $width, $height, $crop, $gravity) {
            if ($event->cover_image_url) {
                // Apply transformation with gravity for smart cropping
                $event->cover_image_url = $cloudinary->transformCloudinaryUrl(
                    $event->cover_image_url,
                    $width,
                    $height,
                    $crop,
                    $gravity
                );
            }
            return $event;
        });
    }
}
