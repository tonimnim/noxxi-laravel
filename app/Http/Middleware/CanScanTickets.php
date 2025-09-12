<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CanScanTickets
{
    /**
     * Handle an incoming request.
     * SECURITY: Comprehensive permission checking for ticket scanning
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            Log::warning('Unauthenticated scanner access attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if request has event_id or ticket_id to validate against
        $eventId = $request->route('event_id') ?? $request->input('event_id');
        $ticketId = $request->input('ticket_id');

        // If ticket_id is provided, get the event_id from the ticket
        if ($ticketId && ! $eventId) {
            $ticket = DB::table('tickets')
                ->where('id', $ticketId)
                ->select('event_id')
                ->first();

            if ($ticket) {
                $eventId = $ticket->event_id;
            }
        }

        // Rate limiting for scanner access (prevent brute force scanning)
        $rateLimitKey = 'scanner_access_'.$user->id;
        $attempts = Cache::get($rateLimitKey, 0);

        if ($attempts > 100) { // Max 100 scan attempts per minute
            Log::alert('Scanner rate limit exceeded', [
                'user_id' => $user->id,
                'attempts' => $attempts,
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Too many requests'], 429);
        }

        Cache::put($rateLimitKey, $attempts + 1, 60);

        // Check if user is an organizer
        $isOrganizer = false;
        $hasPermission = false;

        if ($user->organizer) {
            $isOrganizer = true;

            // If event_id is provided, verify it belongs to this organizer
            if ($eventId) {
                $eventBelongsToOrganizer = DB::table('events')
                    ->where('id', $eventId)
                    ->where('organizer_id', $user->organizer->id)
                    ->exists();

                if (! $eventBelongsToOrganizer) {
                    Log::warning('Organizer attempted to scan ticket for different organizer', [
                        'user_id' => $user->id,
                        'organizer_id' => $user->organizer->id,
                        'event_id' => $eventId,
                        'ip' => $request->ip(),
                    ]);

                    return response()->json(['error' => 'You do not have permission to scan tickets for this event'], 403);
                }
            }

            $hasPermission = true;
        }

        // Check manager permissions
        if (! $isOrganizer || $eventId) {
            $managerPermission = DB::table('organizer_managers')
                ->where('user_id', $user->id)
                ->where('can_scan_tickets', true)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('valid_until')
                        ->orWhere('valid_until', '>', now());
                })
                ->first();

            if ($managerPermission) {
                // If event_id is provided, verify manager has access to this specific event
                if ($eventId) {
                    // First check if event belongs to manager's organizer
                    $event = DB::table('events')
                        ->where('id', $eventId)
                        ->select('organizer_id')
                        ->first();

                    if (! $event || $event->organizer_id !== $managerPermission->organizer_id) {
                        Log::warning('Manager attempted to scan ticket for different organizer', [
                            'user_id' => $user->id,
                            'manager_organizer_id' => $managerPermission->organizer_id,
                            'event_organizer_id' => $event ? $event->organizer_id : null,
                            'event_id' => $eventId,
                            'ip' => $request->ip(),
                        ]);

                        return response()->json(['error' => 'You do not have permission to scan tickets for this event'], 403);
                    }

                    // Check if manager has access to specific events only
                    if (! empty($managerPermission->event_ids)) {
                        $allowedEventIds = json_decode($managerPermission->event_ids, true) ?: [];

                        if (! in_array($eventId, $allowedEventIds)) {
                            Log::warning('Manager attempted to scan ticket for restricted event', [
                                'user_id' => $user->id,
                                'event_id' => $eventId,
                                'allowed_events' => $allowedEventIds,
                                'ip' => $request->ip(),
                            ]);

                            return response()->json(['error' => 'You do not have permission to scan tickets for this event'], 403);
                        }
                    }
                }

                $hasPermission = true;
            }
        }

        if (! $hasPermission) {
            Log::warning('Unauthorized scanner access attempt', [
                'user_id' => $user->id,
                'event_id' => $eventId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['error' => 'You do not have permission to scan tickets'], 403);
        }

        // Add scanner context to request for use in controllers
        $request->merge([
            'scanner_context' => [
                'user_id' => $user->id,
                'is_organizer' => $isOrganizer,
                'is_manager' => ! $isOrganizer && $hasPermission,
                'event_id' => $eventId,
                'timestamp' => now()->toIso8601String(),
                'ip' => $request->ip(),
            ],
        ]);

        // Log successful scanner access for audit
        Log::info('Scanner access granted', [
            'user_id' => $user->id,
            'event_id' => $eventId,
            'is_organizer' => $isOrganizer,
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}
