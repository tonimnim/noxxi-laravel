<?php

namespace App\Traits;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

trait HasScannerPermissions
{
    /**
     * Check if user can scan a specific ticket
     */
    protected function userCanScanTicket(Ticket $ticket): bool
    {
        $user = auth()->user();

        // Check if user is the organizer of the event
        if ($user->organizer && $ticket->event->organizer_id === $user->organizer->id) {
            return true;
        }

        // Check manager permissions
        $managerPermission = DB::table('organizer_managers')
            ->where('user_id', $user->id)
            ->where('organizer_id', $ticket->event->organizer_id)
            ->where('can_scan_tickets', true)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            })
            ->first();

        if ($managerPermission) {
            // Check if manager has access to all events or this specific event
            if (empty($managerPermission->event_ids)) {
                return true; // Access to all organizer's events
            }

            $allowedEventIds = json_decode($managerPermission->event_ids, true) ?: [];

            return in_array($ticket->event_id, $allowedEventIds);
        }

        return false;
    }

    /**
     * Check if user can manage an event
     */
    protected function userCanManageEvent(Event $event): bool
    {
        $user = auth()->user();

        // Check if user is the organizer
        if ($user->organizer && $event->organizer_id === $user->organizer->id) {
            return true;
        }

        // Check manager permissions
        return $this->checkManagerPermission($user, $event);
    }

    /**
     * Check manager permission for an event
     */
    protected function checkManagerPermission($user, Event $event): bool
    {
        $managerPermission = DB::table('organizer_managers')
            ->where('user_id', $user->id)
            ->where('organizer_id', $event->organizer_id)
            ->where('can_scan_tickets', true)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            })
            ->first();

        if ($managerPermission) {
            if (empty($managerPermission->event_ids)) {
                return true;
            }

            $allowedEventIds = json_decode($managerPermission->event_ids, true) ?: [];

            return in_array($event->id, $allowedEventIds);
        }

        return false;
    }

    /**
     * Get allowed events for the current user
     */
    protected function getAllowedEvents($user)
    {
        $eventIds = [];

        // Get events if user is an organizer
        if ($user->organizer) {
            $organizerEvents = Event::where('organizer_id', $user->organizer->id)
                ->where('status', 'published')
                ->where('event_date', '>=', now()->subDays(1))
                ->pluck('id')
                ->toArray();

            $eventIds = array_merge($eventIds, $organizerEvents);
        }

        // Get events from manager permissions
        $managerPermissions = DB::table('organizer_managers')
            ->where('user_id', $user->id)
            ->where('can_scan_tickets', true)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            })
            ->get();

        foreach ($managerPermissions as $permission) {
            if (empty($permission->event_ids)) {
                // Has access to all organizer's events
                $organizerEvents = Event::where('organizer_id', $permission->organizer_id)
                    ->where('status', 'published')
                    ->where('event_date', '>=', now()->subDays(1))
                    ->pluck('id')
                    ->toArray();

                $eventIds = array_merge($eventIds, $organizerEvents);
            } else {
                // Has access to specific events
                $specificEvents = json_decode($permission->event_ids, true) ?: [];
                $eventIds = array_merge($eventIds, $specificEvents);
            }
        }

        // Remove duplicates and load events
        $eventIds = array_unique($eventIds);

        return Event::whereIn('id', $eventIds)
            ->select(['id', 'title', 'venue_name', 'event_date', 'organizer_id'])
            ->with('organizer:id,business_name')
            ->orderBy('event_date')
            ->get();
    }
}
