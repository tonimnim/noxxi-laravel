<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TicketValidationController extends Controller
{
    protected QrCodeService $qrService;
    
    public function __construct(QrCodeService $qrService)
    {
        $this->qrService = $qrService;
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
            // Verify organizer has access to this event
            $organizer = Auth::user()->organizer;
            if ($organizer) {
                $event = Event::find($result['event']['id']);
                if ($event && $event->organizer_id !== $organizer->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to validate tickets for this event'
                    ], 403);
                }
            }
        }
        
        return response()->json($result);
    }
    
    /**
     * Check in a ticket (mark as used)
     */
    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|uuid',
            'gate_id' => 'nullable|string|max:50',
            'device_id' => 'nullable|string|max:100',
        ]);
        
        $ticket = Ticket::find($validated['ticket_id']);
        
        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        }
        
        // Verify organizer has access
        $organizer = Auth::user()->organizer;
        if (!$organizer || $ticket->event->organizer_id !== $organizer->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to check in this ticket'
            ], 403);
        }
        
        $result = $this->qrService->checkInTicket(
            $validated['ticket_id'],
            Auth::id(),
            $validated['gate_id'] ?? null,
            $validated['device_id'] ?? null
        );
        
        // Log check-in activity
        if ($result['success']) {
            activity()
                ->performedOn($ticket)
                ->causedBy(Auth::user())
                ->withProperties([
                    'gate_id' => $validated['gate_id'],
                    'device_id' => $validated['device_id'],
                ])
                ->log('Ticket checked in');
        }
        
        return response()->json($result);
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
            ]
        ]);
    }
    
    /**
     * Get offline validation manifest for an event
     */
    public function getManifest($eventId)
    {
        $event = Event::find($eventId);
        
        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }
        
        // Verify organizer has access
        $organizer = Auth::user()->organizer;
        if (!$organizer || $event->organizer_id !== $organizer->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this manifest'
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
        
        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found'
            ], 404);
        }
        
        // Verify organizer has access
        $organizer = Auth::user()->organizer;
        if (!$organizer || $event->organizer_id !== $organizer->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view these statistics'
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
     * Manually validate a ticket by code (backup method)
     */
    public function validateByCode(Request $request)
    {
        $validated = $request->validate([
            'ticket_code' => 'required|string',
            'event_id' => 'required|uuid',
        ]);
        
        $ticket = Ticket::where('ticket_code', $validated['ticket_code'])
            ->where('event_id', $validated['event_id'])
            ->first();
        
        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        }
        
        // Verify organizer has access
        $organizer = Auth::user()->organizer;
        if (!$organizer || $ticket->event->organizer_id !== $organizer->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to validate this ticket'
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
        
        if (!$ticket->isValid()) {
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