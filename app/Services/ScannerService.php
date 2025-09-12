<?php

namespace App\Services;

use App\Jobs\ProcessTicketCheckIn;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScannerService
{
    /**
     * Process ticket check-in
     */
    public function checkInTicket(Ticket $ticket, array $data): array
    {
        // Check if already used
        if ($ticket->status === 'used') {
            // Check if it's a duplicate scan by same user (idempotent)
            if ($ticket->used_by === Auth::id()) {
                return [
                    'success' => true,
                    'message' => 'Ticket already checked in',
                    'duplicate' => true,
                    'checked_in_at' => $ticket->used_at->toIso8601String(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Ticket already used by another scanner',
                'used_at' => $ticket->used_at->toIso8601String(),
                'used_by' => $ticket->used_by,
            ];
        }

        // Validate ticket status
        if (! in_array($ticket->status, ['valid', 'transferred'])) {
            return [
                'success' => false,
                'message' => 'Ticket is not valid for check-in',
                'status' => $ticket->status,
            ];
        }

        // Check event date (warn if not today)
        $eventDate = $ticket->event->event_date;
        $isToday = $eventDate->isToday();
        $isPast = $eventDate->isPast();
        $isFuture = $eventDate->isFuture() && ! $isToday;

        if ($isPast && ! ($data['force'] ?? false)) {
            return [
                'success' => false,
                'warning' => true,
                'message' => 'This event has already passed. Are you sure you want to check in this ticket?',
                'event_date' => $eventDate->toIso8601String(),
                'require_confirmation' => true,
            ];
        }

        if ($isFuture && ! ($data['force'] ?? false)) {
            return [
                'success' => false,
                'warning' => true,
                'message' => 'This event is in the future. Are you sure you want to check in this ticket?',
                'event_date' => $eventDate->toIso8601String(),
                'require_confirmation' => true,
            ];
        }

        // Perform check-in using the queue job for consistency
        ProcessTicketCheckIn::dispatch([
            'ticket_id' => $ticket->id,
            'event_id' => $ticket->event_id,
            'user_id' => Auth::id(),
            'device_id' => $data['device_id'] ?? 'web_'.substr(md5(request()->userAgent()), 0, 8),
            'scanned_at' => now(),
        ]);

        // Optimistically mark as checked in locally for immediate response
        $this->cacheCheckIn($ticket->id);

        // Log successful check-in
        Log::info('Web scanner check-in completed', [
            'ticket_id' => $ticket->id,
            'event_id' => $ticket->event_id,
            'user_id' => Auth::id(),
        ]);

        return [
            'success' => true,
            'message' => 'Ticket checked in successfully',
            'ticket' => [
                'id' => $ticket->id,
                'code' => $ticket->ticket_code,
                'holder_name' => $ticket->holder_name,
                'type' => $ticket->ticket_type,
                'seat' => $ticket->seat_number,
            ],
            'event' => [
                'id' => $ticket->event->id,
                'title' => $ticket->event->title,
                'venue' => $ticket->event->venue_name,
            ],
            'check_in_time' => now()->toIso8601String(),
        ];
    }

    /**
     * Cache check-in data for quick duplicate detection
     */
    private function cacheCheckIn(string $ticketId): void
    {
        Cache::put('ticket_checked_'.$ticketId, [
            'user_id' => Auth::id(),
            'checked_at' => now()->toIso8601String(),
        ], 300); // 5 minutes
    }
}
