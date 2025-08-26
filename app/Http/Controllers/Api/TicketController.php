<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Ticket;
use App\Services\TicketPdfService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    use ApiResponse;

    protected TicketPdfService $ticketPdfService;

    public function __construct(TicketPdfService $ticketPdfService)
    {
        $this->ticketPdfService = $ticketPdfService;
    }

    /**
     * Get all tickets for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        // Optimized query with select specific columns
        $query = Ticket::query()
            ->select([
                'tickets.id', 'tickets.booking_id', 'tickets.event_id', 'tickets.ticket_code', 'tickets.ticket_type',
                'tickets.price', 'tickets.currency', 'tickets.holder_name', 'tickets.seat_number', 'tickets.seat_section',
                'tickets.status', 'tickets.valid_from', 'tickets.valid_until', 'tickets.created_at',
            ])
            ->where('tickets.assigned_to', Auth::id())
            ->whereIn('tickets.status', ['valid', 'transferred']);

        // Apply filters if provided
        if ($request->has('event_id')) {
            $query->where('tickets.event_id', $request->event_id);
        }

        if ($request->has('ticket_type')) {
            $query->where('tickets.ticket_type', $request->ticket_type);
        }

        // Eager load relationships with specific columns
        $query->with([
            'event:id,title,venue_name,venue_address,city,event_date,end_date,cover_image_url,slug',
            'booking:id,booking_reference,total_amount,currency',
        ]);

        // Order by event date for better UX
        $query->join('events', 'tickets.event_id', '=', 'events.id')
            ->orderBy('events.event_date', 'desc')
            ->orderBy('tickets.created_at', 'desc')
            ->select('tickets.*'); // Ensure we only select ticket columns

        $tickets = $query->paginate($request->per_page ?? 20);

        return $this->success([
            'tickets' => $tickets->items(),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ], 'Tickets retrieved successfully');
    }

    /**
     * Get upcoming tickets for the authenticated user
     */
    public function upcoming(Request $request): JsonResponse
    {
        $query = Ticket::query()
            ->select([
                'tickets.id', 'tickets.booking_id', 'tickets.event_id', 'tickets.ticket_code',
                'tickets.ticket_type', 'tickets.price', 'tickets.currency', 'tickets.holder_name',
                'tickets.seat_number', 'tickets.seat_section', 'tickets.status',
                'tickets.valid_from', 'tickets.valid_until', 'tickets.created_at',
            ])
            ->join('events', 'tickets.event_id', '=', 'events.id')
            ->where('tickets.assigned_to', Auth::id())
            ->whereIn('tickets.status', ['valid', 'transferred'])
            ->where('events.event_date', '>=', now())
            ->where('events.status', 'published');

        // Eager load relationships with specific columns
        $query->with([
            'event:id,title,venue_name,venue_address,city,event_date,end_date,cover_image_url,slug,description',
            'booking:id,booking_reference',
        ]);

        // Order by nearest event first
        $query->orderBy('events.event_date', 'asc')
            ->orderBy('tickets.created_at', 'desc');

        $tickets = $query->paginate($request->per_page ?? 20);

        // Group tickets by date for better UX
        $groupedTickets = $tickets->getCollection()->groupBy(function ($ticket) {
            return $ticket->event->event_date->format('Y-m-d');
        });

        return $this->success([
            'tickets' => $tickets->items(),
            'grouped_by_date' => $groupedTickets,
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ], 'Upcoming tickets retrieved successfully');
    }

    /**
     * Get past tickets for the authenticated user
     */
    public function past(Request $request): JsonResponse
    {
        $query = Ticket::query()
            ->select([
                'tickets.id', 'tickets.booking_id', 'tickets.event_id', 'tickets.ticket_code',
                'tickets.ticket_type', 'tickets.price', 'tickets.currency', 'tickets.holder_name',
                'tickets.seat_number', 'tickets.seat_section', 'tickets.status',
                'tickets.used_at', 'tickets.created_at',
            ])
            ->join('events', 'tickets.event_id', '=', 'events.id')
            ->where('tickets.assigned_to', Auth::id())
            ->where('events.event_date', '<', now());

        // Include all past ticket statuses for history
        if (! $request->has('include_all')) {
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

        return $this->success([
            'tickets' => $tickets->items(),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ], 'Past tickets retrieved successfully');
    }

    /**
     * Get a specific ticket with full details
     */
    public function show($id): JsonResponse
    {
        // Use cache for frequently accessed tickets
        $cacheKey = "ticket_details_{$id}_{Auth::id()}";

        $ticket = cache()->remember($cacheKey, 300, function () use ($id) {
            return Ticket::where('id', $id)
                ->where('assigned_to', Auth::id())
                ->with([
                    'event' => function ($query) {
                        $query->select('id', 'title', 'slug', 'description', 'venue_name',
                            'venue_address', 'city', 'event_date', 'end_date',
                            'cover_image_url', 'organizer_id', 'category_id', 'currency');
                    },
                    'event.category:id,name,slug',
                    'event.organizer:id,business_name',
                    'booking:id,booking_reference,total_amount,currency,created_at',
                ])
                ->first();
        });

        if (! $ticket) {
            return $this->notFound('Ticket not found');
        }

        // Include QR code data and additional information
        $ticketData = $ticket->toArray();
        // QR codes are now generated on-demand via /api/v1/tickets/{id}/qr endpoint

        // Add status information
        $ticketData['status_info'] = [
            'is_valid' => $ticket->isValid(),
            'is_upcoming' => $ticket->event->event_date->isFuture(),
            'is_transferable' => $this->isTicketTransferable($ticket),
            'days_until_event' => now()->diffInDays($ticket->event->event_date, false),
        ];

        return $this->success($ticketData, 'Ticket details retrieved successfully');
    }

    /**
     * Get tickets for a specific booking
     */
    public function byBooking($bookingId): JsonResponse
    {
        $booking = Booking::where('id', $bookingId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $booking) {
            return $this->notFound('Booking not found');
        }

        // Optimized query with specific columns
        $tickets = Ticket::select([
            'id', 'booking_id', 'event_id', 'ticket_code', 'ticket_type',
            'price', 'currency', 'holder_name', 'holder_email',
            'seat_number', 'seat_section', 'status',
            'valid_from', 'valid_until',
        ])
            ->where('booking_id', $bookingId)
            ->with(['event:id,title,venue_name,venue_address,city,event_date,end_date,cover_image_url'])
            ->get();

        // Return tickets (QR codes generated on-demand via secure endpoint)
        $tickets = $tickets->map(function ($ticket) {
            return $ticket->toArray();
        });

        return $this->success([
            'booking' => [
                'id' => $booking->id,
                'reference' => $booking->booking_reference,
                'total_amount' => $booking->total_amount,
                'currency' => $booking->currency,
                'status' => $booking->status,
                'created_at' => $booking->created_at->toIso8601String(),
            ],
            'tickets' => $tickets,
            'ticket_count' => $tickets->count(),
        ], 'Booking tickets retrieved successfully');
    }

    /**
     * Transfer a ticket to another user
     */
    public function transfer(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'recipient_email' => 'required|email|exists:users,email',
            'reason' => 'nullable|string|max:255',
        ]);

        $ticket = Ticket::where('id', $id)
            ->where('assigned_to', Auth::id())
            ->with('event')
            ->first();

        if (! $ticket) {
            return $this->notFound('Ticket not found');
        }

        if ($ticket->status !== 'valid') {
            return $this->error('This ticket cannot be transferred', 400);
        }

        // Check if ticket type allows transfers
        if (! $this->isTicketTransferable($ticket)) {
            return $this->error('This ticket type is not transferable', 400);
        }

        // Check if event has already passed
        if ($ticket->event->event_date->isPast()) {
            return $this->error('Cannot transfer tickets for past events', 400);
        }

        $recipient = \App\Models\User::where('email', $validated['recipient_email'])->first();

        if ((string) $recipient->id === (string) Auth::id()) {
            return $this->error('Cannot transfer ticket to yourself', 400);
        }

        DB::beginTransaction();
        try {
            // Transfer the ticket
            $ticket->transferTo($recipient->id, Auth::id(), $validated['reason'] ?? null);

            // Clear cache for both users
            cache()->forget("ticket_details_{$id}_{Auth::id()}");
            cache()->forget("ticket_details_{$id}_{$recipient->id}");

            DB::commit();

            return $this->success([
                'ticket_id' => $ticket->id,
                'transferred_to' => $recipient->full_name,
                'transferred_to_email' => $recipient->email,
                'transferred_at' => $ticket->transferred_at->toIso8601String(),
                'reason' => $validated['reason'] ?? null,
            ], 'Ticket transferred successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Failed to transfer ticket: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get transfer history for a ticket
     */
    public function transferHistory($id): JsonResponse
    {
        $ticket = Ticket::where('id', $id)
            ->where(function ($query) {
                $query->where('assigned_to', Auth::id())
                    ->orWhere('transferred_from', Auth::id())
                    ->orWhere('transferred_to', Auth::id());
            })
            ->with(['transferredFrom:id,full_name,email', 'transferredTo:id,full_name,email', 'assignedTo:id,full_name,email'])
            ->first();

        if (! $ticket) {
            return $this->notFound('Ticket not found or you do not have access to its history');
        }

        $history = [];

        if ($ticket->transferred_from) {
            $history[] = [
                'from' => [
                    'name' => $ticket->transferredFrom->full_name ?? 'Unknown',
                    'email' => $ticket->transferredFrom->email ?? null,
                ],
                'to' => [
                    'name' => $ticket->transferredTo->full_name ?? 'Unknown',
                    'email' => $ticket->transferredTo->email ?? null,
                ],
                'date' => $ticket->transferred_at->toIso8601String(),
                'reason' => $ticket->transfer_reason,
            ];
        }

        return $this->success([
            'ticket_id' => $ticket->id,
            'ticket_code' => $ticket->ticket_code,
            'current_owner' => [
                'name' => $ticket->assignedTo->full_name,
                'email' => $ticket->assignedTo->email,
                'is_you' => $ticket->assigned_to === Auth::id(),
            ],
            'transfer_history' => $history,
            'transfer_count' => count($history),
        ], 'Transfer history retrieved successfully');
    }

    /**
     * Download ticket as PDF (for backup purposes)
     * Returns both HTML for web and structured data for mobile apps
     */
    public function download($id): JsonResponse
    {
        $ticket = Ticket::where('id', $id)
            ->where('assigned_to', Auth::id())
            ->with(['event', 'booking'])
            ->first();

        if (! $ticket) {
            return $this->notFound('Ticket not found');
        }

        // Get PDF data from the service
        $pdfData = $this->ticketPdfService->getTicketPdfData($ticket);

        // Generate HTML for web clients
        $html = $this->ticketPdfService->generateTicketHtml($ticket);

        // Generate filename
        $filename = $this->ticketPdfService->generateTicketFilename($ticket);

        return $this->success([
            'ticket_data' => $pdfData,
            'html' => $html,
            'filename' => $filename,
            // QR codes generated on-demand via secure endpoint
            'wallet_passes' => [
                'apple' => $this->ticketPdfService->getAppleWalletPassData($ticket),
                'google' => $this->ticketPdfService->getGoogleWalletObjectData($ticket),
            ],
        ], 'Ticket download data retrieved successfully');
    }


    /**
     * Check if ticket is transferable
     */
    private function isTicketTransferable(Ticket $ticket): bool
    {
        $ticketTypeConfig = collect($ticket->event->ticket_types)
            ->firstWhere('name', $ticket->ticket_type);

        return $ticketTypeConfig['transferable'] ?? true;
    }
}
