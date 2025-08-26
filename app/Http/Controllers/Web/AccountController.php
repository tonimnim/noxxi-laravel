<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get upcoming tickets with optimized query
        $upcomingTickets = $this->getUpcomingTickets($user);
        
        // Pass data to view as JSON for Vue component
        return view('account', [
            'initialData' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'tickets' => $upcomingTickets,
            ]
        ]);
    }
    
    private function getUpcomingTickets($user)
    {
        // No caching - always get fresh data for real-time updates
        // Optimized query using Eloquent relationships properly
        $tickets = Ticket::with([
                'event' => function ($query) {
                    $query->select('id', 'title', 'venue_name', 'venue_address', 'city', 
                                  'event_date', 'end_date', 'cover_image_url', 'slug', 
                                  'description', 'qr_secret_key', 'status', 'category_id', 'organizer_id');
                },
                'event.category:id,name,parent_id',
                'event.organizer:id,business_name',
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
            ->limit(50) // Increased limit for better user experience
            ->get();
        
        // Ensure events have QR secret keys for secure generation
        $tickets->each(function ($ticket) {
            if ($ticket->event && !$ticket->event->qr_secret_key) {
                $ticket->event->qr_secret_key = \Str::random(32);
                $ticket->event->save();
            }
        });
        
        // Return tickets as arrays (QR codes will be generated on-demand via secure endpoint)
        return $tickets->map(function ($ticket) {
            return $ticket->toArray();
        });
    }
}