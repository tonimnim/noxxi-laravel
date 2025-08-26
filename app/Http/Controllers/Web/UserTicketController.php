<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserTicketController extends Controller
{
    /**
     * Get upcoming tickets for the authenticated user
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not authenticated'
            ], 401);
        }

        // No caching - always get fresh data for real-time updates
        // This ensures tickets appear immediately after purchase
        $tickets = Ticket::with([
                'event' => function ($query) {
                    $query->select('id', 'title', 'venue_name', 'venue_address', 'city', 
                                  'event_date', 'end_date', 'cover_image_url', 'slug', 
                                  'description', 'qr_secret_key', 'status');
                },
                'booking:id,booking_reference'
            ])
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['valid', 'transferred'])
            ->whereHas('event', function ($query) {
                $query->where('event_date', '>=', now())
                      ->where('status', 'published');
            })
            ->orderByRaw('(SELECT event_date FROM events WHERE events.id = tickets.event_id) ASC')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        // Ensure event has QR secret key for secure generation
        $tickets->getCollection()->each(function ($ticket) {
            if ($ticket->event && !$ticket->event->qr_secret_key) {
                $ticket->event->qr_secret_key = \Str::random(32);
                $ticket->event->save();
            }
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'tickets' => $tickets->items(),
                'pagination' => [
                    'current_page' => $tickets->currentPage(),
                    'last_page' => $tickets->lastPage(),
                    'per_page' => $tickets->perPage(),
                    'total' => $tickets->total(),
                ],
            ],
            'message' => 'Upcoming tickets retrieved successfully'
        ]);
    }

    /**
     * Get past tickets for the authenticated user
     */
    public function past(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not authenticated'
            ], 401);
        }

        $query = Ticket::query()
            ->select([
                'tickets.id', 'tickets.booking_id', 'tickets.event_id', 'tickets.ticket_code',
                'tickets.ticket_type', 'tickets.price', 'tickets.currency', 'tickets.holder_name',
                'tickets.seat_number', 'tickets.seat_section', 'tickets.status',
                'tickets.used_at', 'tickets.created_at',
            ])
            ->join('events', 'tickets.event_id', '=', 'events.id')
            ->where('tickets.assigned_to', $user->id)
            ->where('events.event_date', '<', now());

        // Include all past ticket statuses for history
        if (!$request->has('include_all')) {
            $query->whereIn('tickets.status', ['valid', 'transferred', 'used']);
        }

        // Eager load relationships
        $query->with([
            'event:id,title,venue_name,venue_address,city,event_date,end_date,cover_image_url,slug',
            'booking:id,booking_reference',
        ]);

        // Order by most recent event first
        $query->orderBy('events.event_date', 'desc')
            ->orderBy('tickets.created_at', 'desc');

        $tickets = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'tickets' => $tickets->items(),
                'pagination' => [
                    'current_page' => $tickets->currentPage(),
                    'last_page' => $tickets->lastPage(),
                    'per_page' => $tickets->perPage(),
                    'total' => $tickets->total(),
                ],
            ],
            'message' => 'Past tickets retrieved successfully'
        ]);
    }

    /**
     * Get a specific ticket (QR will be fetched separately)
     */
    public function show($id): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not authenticated'
            ], 401);
        }

        $ticket = Ticket::where('id', $id)
            ->where('assigned_to', $user->id)
            ->with([
                'event:id,title,venue_name,venue_address,city,event_date,end_date,cover_image_url,slug,description,terms_conditions,qr_secret_key',
                'booking:id,booking_reference,total_amount,currency',
            ])
            ->first();

        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket not found'
            ], 404);
        }

        // Ensure event has QR secret key for secure generation
        if ($ticket->event && !$ticket->event->qr_secret_key) {
            $ticket->event->qr_secret_key = \Str::random(32);
            $ticket->event->save();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'ticket' => $ticket->toArray()
            ],
            'message' => 'Ticket retrieved successfully'
        ]);
    }
}