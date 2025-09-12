<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get upcoming tickets with optimized query
        $upcomingTickets = $this->getUpcomingTickets($user);

        // Check scanner permissions
        $canScanTickets = $this->checkScannerPermissions($user);
        $scannerData = $canScanTickets ? $this->getScannerData($user) : null;

        // Pass data to view as JSON for Vue component
        return view('account', [
            'initialData' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'tickets' => $upcomingTickets,
                'canScanTickets' => $canScanTickets,
                'scannerData' => $scannerData,
            ],
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
            'booking:id,booking_reference',
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
            if ($ticket->event && ! $ticket->event->qr_secret_key) {
                $ticket->event->qr_secret_key = \Str::random(32);
                $ticket->event->save();
            }
        });

        // Return tickets as arrays (QR codes will be generated on-demand via secure endpoint)
        return $tickets->map(function ($ticket) {
            return $ticket->toArray();
        });
    }

    /**
     * Check if user has permission to scan tickets
     * Security: Verify both organizer ownership and manager relationships
     */
    private function checkScannerPermissions($user)
    {
        // Check if user is an organizer
        if ($user->organizer) {
            return true;
        }

        // Check if user has active manager permissions with scan rights
        // SECURITY: Direct database query to prevent relationship manipulation
        $hasPermission = DB::table('organizer_managers')
            ->where('user_id', $user->id)
            ->where('can_scan_tickets', true)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            })
            ->exists();

        return $hasPermission;
    }

    /**
     * Get scanner data including allowed events
     * SECURITY: Strict filtering of events based on ownership/management
     */
    private function getScannerData($user)
    {
        $allowedEventIds = [];
        $organizerIds = [];

        // If user is an organizer, get their events
        if ($user->organizer) {
            $organizerIds[] = $user->organizer->id;

            // Get all active events for this organizer
            $events = Event::where('organizer_id', $user->organizer->id)
                ->where('status', 'published')
                ->where('event_date', '>=', now()->subDays(1)) // Include yesterday's events
                ->select(['id', 'title', 'venue_name', 'event_date', 'qr_secret_key'])
                ->get();

            $allowedEventIds = $events->pluck('id')->toArray();
        }

        // Check manager permissions (even if also an organizer, might manage others)
        $managerPermissions = DB::table('organizer_managers')
            ->where('user_id', $user->id)
            ->where('can_scan_tickets', true)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            })
            ->get(['organizer_id', 'event_ids']);

        foreach ($managerPermissions as $permission) {
            // SECURITY: Verify organizer exists and is active
            $organizerExists = DB::table('organizers')
                ->where('id', $permission->organizer_id)
                ->where('is_active', true)
                ->exists();

            if (! $organizerExists) {
                continue; // Skip invalid/inactive organizers
            }

            $organizerIds[] = $permission->organizer_id;

            // Get allowed events for this manager permission
            if (empty($permission->event_ids)) {
                // Manager has access to all organizer's events
                $events = Event::where('organizer_id', $permission->organizer_id)
                    ->where('status', 'published')
                    ->where('event_date', '>=', now()->subDays(1))
                    ->pluck('id')
                    ->toArray();

                $allowedEventIds = array_merge($allowedEventIds, $events);
            } else {
                // Manager has access to specific events only
                $eventIds = json_decode($permission->event_ids, true) ?: [];

                // SECURITY: Verify each event belongs to the correct organizer
                $verifiedEvents = Event::whereIn('id', $eventIds)
                    ->where('organizer_id', $permission->organizer_id)
                    ->where('status', 'published')
                    ->where('event_date', '>=', now()->subDays(1))
                    ->pluck('id')
                    ->toArray();

                $allowedEventIds = array_merge($allowedEventIds, $verifiedEvents);
            }
        }

        // Remove duplicates and ensure unique event IDs
        $allowedEventIds = array_unique($allowedEventIds);

        // SECURITY: Final verification - load only allowed events with minimal data
        $allowedEvents = Event::whereIn('id', $allowedEventIds)
            ->select([
                'id',
                'title',
                'venue_name',
                'event_date',
                'organizer_id',
                'check_in_enabled',
                'check_in_opens_at',
                'check_in_closes_at',
                'allow_immediate_check_in',
            ])
            ->with(['organizer:id,business_name'])
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'venue' => $event->venue_name,
                    'date' => $event->event_date->toIso8601String(),
                    'organizer_id' => $event->organizer_id,
                    'organizer_name' => $event->organizer->business_name ?? 'Unknown',
                    'check_in_enabled' => $event->check_in_enabled ?? true,
                    'check_in_window' => [
                        'opens_at' => $event->check_in_opens_at?->toIso8601String(),
                        'closes_at' => $event->check_in_closes_at?->toIso8601String(),
                        'allow_immediate' => $event->allow_immediate_check_in ?? true,
                    ],
                ];
            });

        return [
            'allowedEvents' => $allowedEvents,
            'organizerIds' => array_unique($organizerIds),
            'permissions' => [
                'isOrganizer' => (bool) $user->organizer,
                'isManager' => count($managerPermissions) > 0,
                'totalEvents' => count($allowedEventIds),
            ],
            // Security token for scanner session validation
            'scannerToken' => hash_hmac('sha256', $user->id.':'.implode(',', $allowedEventIds), config('app.key')),
        ];
    }
}
