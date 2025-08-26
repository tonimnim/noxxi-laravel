<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrganizerListingController extends Controller
{
    /**
     * Get all listings for the authenticated organizer
     */
    public function index(Request $request)
    {
        $organizer = Auth::user()->organizer;

        if (! $organizer) {
            return response()->json([
                'success' => false,
                'message' => 'Organizer profile not found',
            ], 404);
        }

        $query = Event::where('organizer_id', $organizer->id)
            ->with(['category:id,name,parent_id']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'ilike', '%'.$request->search.'%')
                    ->orWhere('venue_name', 'ilike', '%'.$request->search.'%');
            });
        }

        $listings = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $listings,
        ]);
    }

    /**
     * Create a new listing
     */
    public function store(Request $request)
    {
        $organizer = Auth::user()->organizer;

        if (! $organizer) {
            return response()->json([
                'success' => false,
                'message' => 'Organizer profile not found',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'category_id' => 'required|exists:event_categories,id',
            'venue_name' => 'required|string|max:255',
            'venue_address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'event_date' => 'required|date|after:now',
            'end_date' => 'nullable|date|after:event_date',
            'capacity' => 'required|integer|min:1',
            'currency' => ['required', Rule::in(array_keys(config('currencies.supported', [])))],
            'ticket_types' => 'required|array|min:1',
            'ticket_types.*.name' => 'required|string|max:50',
            'ticket_types.*.price' => 'required|numeric|min:0',
            'ticket_types.*.quantity' => 'required|integer|min:1',
            'ticket_types.*.description' => 'nullable|string|max:500',
            'ticket_types.*.sale_start' => 'nullable|date',
            'ticket_types.*.sale_end' => 'nullable|date|after:ticket_types.*.sale_start',
            'ticket_types.*.max_per_order' => 'nullable|integer|min:1',
            'ticket_types.*.transferable' => 'nullable|boolean',
            'ticket_types.*.refundable' => 'nullable|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'age_restriction' => 'nullable|integer|min:0|max:21',
            'policies' => 'nullable|array',
            'media' => 'nullable|array',
            'marketing' => 'nullable|array',
            'category_metadata' => 'nullable|array',
            'ticket_sales_config' => 'nullable|array',
            'listing_settings' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            // Calculate min and max prices
            $prices = array_column($validated['ticket_types'], 'price');
            $minPrice = min($prices);
            $maxPrice = max($prices);

            // Generate slug
            $slug = Str::slug($validated['title']);
            $count = Event::where('slug', 'like', $slug.'%')->count();
            if ($count > 0) {
                $slug = $slug.'-'.($count + 1);
            }

            // Create event
            $event = Event::create([
                'organizer_id' => $organizer->id,
                'title' => $validated['title'],
                'slug' => $slug,
                'description' => $validated['description'],
                'category_id' => $validated['category_id'],
                'category_metadata' => $validated['category_metadata'] ?? [],
                'venue_name' => $validated['venue_name'],
                'venue_address' => $validated['venue_address'],
                'city' => $validated['city'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'event_date' => $validated['event_date'],
                'end_date' => $validated['end_date'],
                'capacity' => $validated['capacity'],
                'currency' => $validated['currency'],
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'ticket_types' => $validated['ticket_types'],
                'ticket_sales_config' => $validated['ticket_sales_config'] ?? [
                    'max_tickets_per_order' => 10,
                    'show_remaining_tickets' => true,
                    'enable_waiting_list' => false,
                ],
                'tags' => $validated['tags'] ?? [],
                'age_restriction' => $validated['age_restriction'] ?? 0,
                'policies' => $validated['policies'] ?? [],
                'media' => $validated['media'] ?? [],
                'marketing' => $validated['marketing'] ?? [],
                'listing_settings' => $validated['listing_settings'] ?? [
                    'allow_guests' => true,
                    'enable_reviews' => true,
                    'require_approval' => false,
                ],
                'status' => 'draft',
                'qr_secret_key' => Str::random(32),
                'tickets_sold' => 0,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Listing created successfully',
                'data' => $event->load('category'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create listing',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific listing
     */
    public function show($id)
    {
        $organizer = Auth::user()->organizer;

        if (! $organizer) {
            return response()->json([
                'success' => false,
                'message' => 'Organizer profile not found',
            ], 404);
        }

        $event = Event::where('organizer_id', $organizer->id)
            ->with(['category', 'bookings', 'tickets'])
            ->find($id);

        if (! $event) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $event,
        ]);
    }

    /**
     * Update a listing
     */
    public function update(Request $request, $id)
    {
        $organizer = Auth::user()->organizer;

        if (! $organizer) {
            return response()->json([
                'success' => false,
                'message' => 'Organizer profile not found',
            ], 404);
        }

        $event = Event::where('organizer_id', $organizer->id)->find($id);

        if (! $event) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found',
            ], 404);
        }

        // Prevent editing if tickets are sold
        if ($event->tickets_sold > 0 && $request->has('ticket_types')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify ticket types after tickets have been sold',
            ], 400);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:5000',
            'category_id' => 'sometimes|exists:event_categories,id',
            'venue_name' => 'sometimes|string|max:255',
            'venue_address' => 'sometimes|string|max:500',
            'city' => 'sometimes|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'event_date' => 'sometimes|date|after:now',
            'end_date' => 'nullable|date|after:event_date',
            'capacity' => 'sometimes|integer|min:'.$event->tickets_sold,
            'ticket_types' => 'sometimes|array|min:1',
            'tags' => 'nullable|array',
            'age_restriction' => 'nullable|integer|min:0|max:21',
            'policies' => 'nullable|array',
            'media' => 'nullable|array',
            'marketing' => 'nullable|array',
            'category_metadata' => 'nullable|array',
        ]);

        // Update slug if title changed
        if (isset($validated['title']) && $validated['title'] !== $event->title) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Recalculate prices if ticket types changed
        if (isset($validated['ticket_types'])) {
            $prices = array_column($validated['ticket_types'], 'price');
            $validated['min_price'] = min($prices);
            $validated['max_price'] = max($prices);
        }

        $validated['last_modified_at'] = now();
        $validated['modified_by'] = Auth::id();

        $event->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Listing updated successfully',
            'data' => $event->fresh(['category']),
        ]);
    }

    /**
     * Publish a listing
     */
    public function publish($id)
    {
        $organizer = Auth::user()->organizer;

        if (! $organizer) {
            return response()->json([
                'success' => false,
                'message' => 'Organizer profile not found',
            ], 404);
        }

        $event = Event::where('organizer_id', $organizer->id)->find($id);

        if (! $event) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found',
            ], 404);
        }

        if ($event->status === 'published') {
            return response()->json([
                'success' => false,
                'message' => 'Listing is already published',
            ], 400);
        }

        // Validate event is ready for publishing
        if (empty($event->ticket_types)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot publish without ticket types',
            ], 400);
        }

        $event->update([
            'status' => 'published',
            'published_at' => now(),
            'first_published_at' => $event->first_published_at ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Listing published successfully',
            'data' => $event,
        ]);
    }

    /**
     * Delete a listing (soft delete)
     */
    public function destroy($id)
    {
        $organizer = Auth::user()->organizer;

        if (! $organizer) {
            return response()->json([
                'success' => false,
                'message' => 'Organizer profile not found',
            ], 404);
        }

        $event = Event::where('organizer_id', $organizer->id)->find($id);

        if (! $event) {
            return response()->json([
                'success' => false,
                'message' => 'Listing not found',
            ], 404);
        }

        if ($event->tickets_sold > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete listing with sold tickets',
            ], 400);
        }

        $event->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Listing cancelled successfully',
        ]);
    }
}
