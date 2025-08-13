<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Booking;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    protected QrCodeService $qrCodeService;
    
    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }
    
    /**
     * Get all tickets for the authenticated user
     */
    public function index(Request $request)
    {
        $tickets = Ticket::where('assigned_to', Auth::id())
            ->whereIn('status', ['valid', 'transferred'])
            ->with(['event:id,title,venue_name,venue_address,city,event_date,end_date,cover_image_url', 'booking:id,booking_reference'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'data' => $tickets
        ]);
    }
    
    /**
     * Get upcoming tickets for the authenticated user
     */
    public function upcoming(Request $request)
    {
        $tickets = Ticket::where('assigned_to', Auth::id())
            ->whereIn('status', ['valid', 'transferred'])
            ->whereHas('event', function ($query) {
                $query->where('event_date', '>=', now())
                      ->where('status', 'published');
            })
            ->with(['event:id,title,venue_name,venue_address,city,event_date,end_date,cover_image_url', 'booking:id,booking_reference'])
            ->orderBy('events.event_date', 'asc')
            ->join('events', 'tickets.event_id', '=', 'events.id')
            ->select('tickets.*')
            ->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'data' => $tickets
        ]);
    }
    
    /**
     * Get past tickets for the authenticated user
     */
    public function past(Request $request)
    {
        $tickets = Ticket::where('assigned_to', Auth::id())
            ->whereHas('event', function ($query) {
                $query->where('event_date', '<', now());
            })
            ->with(['event:id,title,venue_name,venue_address,city,event_date,end_date,cover_image_url', 'booking:id,booking_reference'])
            ->orderBy('events.event_date', 'desc')
            ->join('events', 'tickets.event_id', '=', 'events.id')
            ->select('tickets.*')
            ->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'data' => $tickets
        ]);
    }
    
    /**
     * Get a specific ticket with full details
     */
    public function show($id)
    {
        $ticket = Ticket::where('id', $id)
            ->where('assigned_to', Auth::id())
            ->with([
                'event',
                'event.category',
                'event.organizer',
                'booking:id,booking_reference,total_amount,currency',
            ])
            ->first();
        
        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        }
        
        // Include QR code data for display in app
        $ticketData = $ticket->toArray();
        $ticketData['qr_data'] = [
            'content' => $this->generateQrContent($ticket),
            'image_url' => $ticket->qr_code,
        ];
        
        return response()->json([
            'success' => true,
            'data' => $ticketData
        ]);
    }
    
    /**
     * Get tickets for a specific booking
     */
    public function byBooking($bookingId)
    {
        $booking = Booking::where('id', $bookingId)
            ->where('user_id', Auth::id())
            ->first();
        
        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }
        
        $tickets = Ticket::where('booking_id', $bookingId)
            ->with(['event:id,title,venue_name,venue_address,city,event_date,end_date,cover_image_url'])
            ->get();
        
        // Add QR data for each ticket
        $tickets = $tickets->map(function ($ticket) {
            $ticketArray = $ticket->toArray();
            $ticketArray['qr_data'] = [
                'content' => $this->generateQrContent($ticket),
                'image_url' => $ticket->qr_code,
            ];
            return $ticketArray;
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'booking' => [
                    'id' => $booking->id,
                    'reference' => $booking->booking_reference,
                    'total_amount' => $booking->total_amount,
                    'currency' => $booking->currency,
                    'status' => $booking->status,
                ],
                'tickets' => $tickets
            ]
        ]);
    }
    
    /**
     * Transfer a ticket to another user
     */
    public function transfer(Request $request, $id)
    {
        $validated = $request->validate([
            'recipient_email' => 'required|email|exists:users,email',
            'reason' => 'nullable|string|max:255',
        ]);
        
        $ticket = Ticket::where('id', $id)
            ->where('assigned_to', Auth::id())
            ->first();
        
        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        }
        
        if ($ticket->status !== 'valid') {
            return response()->json([
                'success' => false,
                'message' => 'This ticket cannot be transferred'
            ], 400);
        }
        
        // Check if ticket type allows transfers
        $ticketTypeConfig = collect($ticket->event->ticket_types)
            ->firstWhere('name', $ticket->ticket_type);
        
        if ($ticketTypeConfig && !($ticketTypeConfig['transferable'] ?? true)) {
            return response()->json([
                'success' => false,
                'message' => 'This ticket type is not transferable'
            ], 400);
        }
        
        $recipient = \App\Models\User::where('email', $validated['recipient_email'])->first();
        
        if ($recipient->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot transfer ticket to yourself'
            ], 400);
        }
        
        // Transfer the ticket
        $ticket->transferTo($recipient->id, Auth::id(), $validated['reason'] ?? null);
        
        return response()->json([
            'success' => true,
            'message' => 'Ticket transferred successfully',
            'data' => [
                'ticket_id' => $ticket->id,
                'transferred_to' => $recipient->full_name,
                'transferred_at' => $ticket->transferred_at->toIso8601String(),
            ]
        ]);
    }
    
    /**
     * Get transfer history for a ticket
     */
    public function transferHistory($id)
    {
        $ticket = Ticket::where('id', $id)
            ->where(function ($query) {
                $query->where('assigned_to', Auth::id())
                      ->orWhere('transferred_from', Auth::id())
                      ->orWhere('transferred_to', Auth::id());
            })
            ->first();
        
        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        }
        
        $history = [];
        
        if ($ticket->transferred_from) {
            $history[] = [
                'from' => $ticket->transferredFrom->full_name ?? 'Unknown',
                'to' => $ticket->transferredTo->full_name ?? 'Unknown',
                'date' => $ticket->transferred_at->toIso8601String(),
                'reason' => $ticket->transfer_reason,
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'ticket_id' => $ticket->id,
                'current_owner' => $ticket->assignedTo->full_name,
                'transfer_history' => $history,
            ]
        ]);
    }
    
    /**
     * Download ticket as PDF (for backup purposes)
     * The app should primarily display the ticket in-app
     */
    public function download($id)
    {
        $ticket = Ticket::where('id', $id)
            ->where('assigned_to', Auth::id())
            ->with(['event', 'booking'])
            ->first();
        
        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found'
            ], 404);
        }
        
        // Return ticket data for app to generate PDF client-side
        // This avoids server-side PDF generation overhead
        return response()->json([
            'success' => true,
            'data' => [
                'ticket' => $ticket,
                'qr_code' => $ticket->qr_code,
                'download_url' => null, // App generates PDF locally
                'template_data' => [
                    'event_name' => $ticket->event->title,
                    'venue' => $ticket->event->venue_name,
                    'date' => $ticket->event->event_date->format('F d, Y g:i A'),
                    'ticket_type' => $ticket->ticket_type,
                    'ticket_code' => $ticket->ticket_code,
                    'holder_name' => $ticket->holder_name,
                    'seat' => $ticket->seat_number,
                    'section' => $ticket->seat_section,
                ]
            ]
        ]);
    }
    
    /**
     * Generate QR content for a ticket
     */
    private function generateQrContent(Ticket $ticket): string
    {
        $qrData = [
            'tid' => $ticket->id,
            'eid' => $ticket->event_id,
            'code' => $ticket->ticket_code,
            'type' => $ticket->ticket_type,
            'exp' => $ticket->event->end_date?->timestamp ?? $ticket->event->event_date->addDay()->timestamp,
            'iat' => now()->timestamp,
        ];
        
        $signature = hash_hmac('sha256', json_encode($qrData), $ticket->event->qr_secret_key);
        
        return base64_encode(json_encode($qrData) . '|' . $signature);
    }
}