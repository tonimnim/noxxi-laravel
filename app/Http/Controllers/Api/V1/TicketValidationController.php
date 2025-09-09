<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\SecureQrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TicketValidationController extends Controller
{
    protected SecureQrService $qrService;

    public function __construct(SecureQrService $qrService)
    {
        $this->qrService = $qrService;
    }

    /**
     * Check if user has permission to manage event (as organizer or manager)
     */
    protected function userCanManageEvent(Event $event): bool
    {
        $user = Auth::user();

        // Check if user is the organizer
        if ($user->organizer && $event->organizer_id === $user->organizer->id) {
            return true;
        }

        // Check if user is a manager with permission for this event
        $managerPermission = $user->activeScannerPermissions()
            ->where('organizer_id', $event->organizer_id)
            ->where('can_scan_tickets', true)
            ->first();

        if ($managerPermission) {
            // Check if manager has access to all events or this specific event
            if (empty($managerPermission->event_ids) || in_array($event->id, $managerPermission->event_ids)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate a ticket QR code
     */
    public function validate(Request $request)
    {
        $validated = $request->validate([
            'qr_content' => 'required|string',
            'gate_id' => 'nullable|string|max:50',
            'device_id' => 'nullable|string|max:100',
        ]);

        // Check if organizer has permission for this event
        $result = $this->qrService->validateQrCode(
            $validated['qr_content'],
            $validated['gate_id'] ?? null
        );

        if ($result['success']) {
            // Verify user has permission to validate tickets for this event
            $event = Event::find($result['event']['id']);
            if ($event && ! $this->userCanManageEvent($event)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to validate tickets for this event',
                ], 403);
            }
        }

        return response()->json($result);
    }

    /**
     * Check in a ticket (mark as used) - Now queued for performance
     */
    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|uuid',
            'gate_id' => 'nullable|string|max:50',
            'device_id' => 'nullable|string|max:100',
            'scanned_at' => 'nullable|date', // For offline scans
        ]);

        // Quick cache check for duplicate scans
        $cacheKey = "ticket_checked_{$validated['ticket_id']}";
        $cached = Cache::get($cacheKey);
        
        if ($cached && $cached['user_id'] === Auth::id()) {
            // Already checked in by same user - return success (idempotent)
            return response()->json([
                'success' => true,
                'message' => 'Ticket already checked in',
                'cached' => true,
                'check_in_time' => $cached['checked_at']
            ]);
        }

        $ticket = Ticket::find($validated['ticket_id']);

        if (! $ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
            ], 404);
        }

        // Verify user has permission to check in tickets for this event
        if (! $this->userCanManageEvent($ticket->event)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to check in this ticket',
            ], 403);
        }

        // Quick status check before queuing
        if ($ticket->status === 'used' && $ticket->used_by === Auth::id()) {
            return response()->json([
                'success' => true,
                'message' => 'Ticket already checked in',
                'check_in_time' => $ticket->used_at->toIso8601String()
            ]);
        }

        // Queue the check-in for async processing
        \App\Jobs\ProcessTicketCheckIn::dispatch([
            'ticket_id' => (string) $validated['ticket_id'],
            'event_id' => (string) $ticket->event_id,
            'user_id' => Auth::id(),
            'gate_id' => $validated['gate_id'] ?? null,
            'device_id' => $validated['device_id'] ?? null,
            'scanned_at' => $validated['scanned_at'] ?? now(),
        ]);

        // Optimistically cache the check-in
        Cache::put($cacheKey, [
            'user_id' => Auth::id(),
            'gate_id' => $validated['gate_id'],
            'checked_at' => now()->toIso8601String()
        ], 300); // 5 minutes

        // Return immediate success (optimistic response)
        return response()->json([
            'success' => true,
            'message' => 'Check-in queued successfully',
            'ticket' => [
                'id' => $ticket->id,
                'code' => $ticket->ticket_code,
                'holder_name' => $ticket->holder_name,
                'type' => $ticket->ticket_type,
            ],
            'queued' => true,
            'check_in_time' => now()->toIso8601String()
        ]);
    }

    /**
     * Batch validate multiple tickets
     */
    public function batchValidate(Request $request)
    {
        $validated = $request->validate([
            'tickets' => 'required|array|max:50',
            'tickets.*.qr_content' => 'required|string',
            'gate_id' => 'nullable|string|max:50',
        ]);

        $results = [];
        foreach ($validated['tickets'] as $index => $ticketData) {
            $results[$index] = $this->qrService->validateQrCode(
                $ticketData['qr_content'],
                $validated['gate_id'] ?? null
            );
        }

        return response()->json([
            'success' => true,
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'valid' => collect($results)->where('success', true)->count(),
                'invalid' => collect($results)->where('success', false)->count(),
            ],
        ]);
    }

    /**
     * Get offline validation manifest for an event
     */
    public function getManifest($eventId)
    {
        $event = Event::find($eventId);

        if (! $event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        // Verify user has permission to access this event's manifest
        if (! $this->userCanManageEvent($event)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this manifest',
            ], 403);
        }

        // Cache manifest for 5 minutes to reduce database load
        $cacheKey = "event_manifest_{$eventId}";
        $manifest = Cache::remember($cacheKey, 300, function () use ($event) {
            return $this->qrService->generateOfflineManifest($event);
        });

        return response()->json([
            'success' => true,
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'venue' => $event->venue_name,
                'date' => $event->event_date->toIso8601String(),
            ],
            'manifest' => $manifest,
            'expires_at' => now()->addMinutes(5)->toIso8601String(),
        ]);
    }

    /**
     * Get real-time check-in statistics for an event
     */
    public function getCheckInStats($eventId)
    {
        $event = Event::find($eventId);

        if (! $event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        // Verify user has permission to view this event's statistics
        if (! $this->userCanManageEvent($event)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view these statistics',
            ], 403);
        }

        $stats = DB::table('tickets')
            ->where('event_id', $eventId)
            ->selectRaw("
                COUNT(*) as total_tickets,
                COUNT(CASE WHEN status = 'used' THEN 1 END) as checked_in,
                COUNT(CASE WHEN status = 'valid' THEN 1 END) as not_checked_in,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
                COUNT(CASE WHEN status = 'transferred' THEN 1 END) as transferred
            ")
            ->first();

        $recentCheckIns = Ticket::where('event_id', $eventId)
            ->where('status', 'used')
            ->orderBy('used_at', 'desc')
            ->limit(10)
            ->get(['id', 'holder_name', 'ticket_type', 'used_at', 'entry_gate']);

        $gateStats = DB::table('tickets')
            ->where('event_id', $eventId)
            ->where('status', 'used')
            ->whereNotNull('entry_gate')
            ->groupBy('entry_gate')
            ->selectRaw('entry_gate, COUNT(*) as count')
            ->get();

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_tickets' => $stats->total_tickets,
                'checked_in' => $stats->checked_in,
                'not_checked_in' => $stats->not_checked_in,
                'cancelled' => $stats->cancelled,
                'transferred' => $stats->transferred,
                'check_in_percentage' => $stats->total_tickets > 0
                    ? round(($stats->checked_in / $stats->total_tickets) * 100, 2)
                    : 0,
            ],
            'recent_check_ins' => $recentCheckIns,
            'gate_statistics' => $gateStats,
            'last_updated' => now()->toIso8601String(),
        ]);
    }

    /**
     * Batch check-in multiple tickets (for offline sync)
     */
    public function batchCheckIn(Request $request)
    {
        $validated = $request->validate([
            'check_ins' => 'required|array|max:100', // Max 100 tickets per batch
            'check_ins.*.ticket_id' => 'required|uuid',
            'check_ins.*.scanned_at' => 'required|date',
            'check_ins.*.gate_id' => 'nullable|string|max:50',
            'check_ins.*.device_id' => 'nullable|string|max:100',
        ]);

        // Generate batch ID for tracking
        $batchId = \Str::uuid()->toString();
        
        // Prepare check-in data with user ID
        $checkInsData = array_map(function ($checkIn) use ($batchId) {
            return array_merge($checkIn, [
                'user_id' => Auth::id(),
                'batch_id' => $batchId,
                'event_id' => Ticket::find($checkIn['ticket_id'])?->event_id
            ]);
        }, $validated['check_ins']);

        // Queue batch processing
        \App\Jobs\ProcessTicketCheckIn::dispatch($checkInsData, true);

        // Return immediate response
        return response()->json([
            'success' => true,
            'message' => 'Batch check-in queued for processing',
            'batch_id' => $batchId,
            'count' => count($checkInsData),
            'status_url' => route('api.v1.batch-status', ['batchId' => $batchId])
        ], 202); // 202 Accepted
    }

    /**
     * Get batch check-in status
     */
    public function getBatchStatus($batchId)
    {
        $checkIns = DB::table('pending_check_ins')
            ->where('batch_id', $batchId)
            ->where('checked_by', Auth::id())
            ->get();

        if ($checkIns->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Batch not found'
            ], 404);
        }

        $stats = [
            'total' => $checkIns->count(),
            'completed' => $checkIns->where('status', 'completed')->count(),
            'pending' => $checkIns->where('status', 'pending')->count(),
            'processing' => $checkIns->where('status', 'processing')->count(),
            'failed' => $checkIns->where('status', 'failed')->count(),
        ];

        return response()->json([
            'success' => true,
            'batch_id' => $batchId,
            'status' => $stats['pending'] > 0 || $stats['processing'] > 0 ? 'processing' : 'completed',
            'statistics' => $stats,
            'details' => $checkIns->map(function ($checkIn) {
                return [
                    'ticket_id' => $checkIn->ticket_id,
                    'status' => $checkIn->status,
                    'error' => $checkIn->error_message,
                    'processed_at' => $checkIn->updated_at
                ];
            })
        ]);
    }

    /**
     * Manually validate a ticket by code (backup method)
     */
    public function validateByCode(Request $request)
    {
        $validated = $request->validate([
            'ticket_code' => 'required|string',
            'event_id' => 'nullable|uuid',  // Made optional for manual entry
        ]);

        // First try with event_id if provided, otherwise just use ticket code
        if (!empty($validated['event_id'])) {
            $ticket = Ticket::where('ticket_code', $validated['ticket_code'])
                ->where('event_id', $validated['event_id'])
                ->with('event')
                ->first();
        } else {
            // For manual entry, just use ticket code (ticket codes are unique)
            $ticket = Ticket::where('ticket_code', $validated['ticket_code'])
                ->with('event')
                ->first();
        }

        if (! $ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
            ], 404);
        }

        // Verify user has permission to validate this ticket
        if (! $this->userCanManageEvent($ticket->event)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to validate this ticket',
            ], 403);
        }

        if ($ticket->status === 'used') {
            return response()->json([
                'success' => false,
                'message' => 'Ticket already used',
                'used_at' => $ticket->used_at->toIso8601String(),
                'entry_gate' => $ticket->entry_gate,
            ]);
        }

        if (! $ticket->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket is not valid',
                'status' => $ticket->status,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket is valid',
            'ticket' => [
                'id' => $ticket->id,
                'code' => $ticket->ticket_code,
                'type' => $ticket->ticket_type,
                'holder_name' => $ticket->holder_name,
                'holder_email' => $ticket->holder_email,
                'seat_number' => $ticket->seat_number,
                'seat_section' => $ticket->seat_section,
            ],
            'can_check_in' => true,
        ]);
    }
}
