<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketService
{
    protected QrCodeService $qrCodeService;
    
    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }
    
    /**
     * Create tickets for a confirmed booking
     */
    public function createTicketsForBooking(Booking $booking): array
    {
        $event = $booking->event;
        $ticketTypes = $booking->ticket_types;
        $customerDetails = $booking->customer_details;
        $createdTickets = [];
        
        DB::beginTransaction();
        
        try {
            $ticketCounter = 1;
            
            foreach ($ticketTypes as $ticketType) {
                $quantity = $ticketType['quantity'] ?? 1;
                $typeName = $ticketType['name'] ?? 'General';
                $price = $ticketType['price'] ?? 0;
                
                for ($i = 0; $i < $quantity; $i++) {
                    // Generate unique ticket code
                    $ticketCode = $this->generateTicketCode($event, $booking);
                    
                    // Determine seat assignment if applicable
                    $seatData = $this->assignSeat($event, $typeName, $ticketCounter);
                    
                    // Create ticket
                    $ticket = Ticket::create([
                        'booking_id' => $booking->id,
                        'event_id' => $event->id,
                        'ticket_code' => $ticketCode,
                        'ticket_type' => $typeName,
                        'price' => $price,
                        'currency' => $event->currency,
                        'seat_number' => $seatData['seat_number'] ?? null,
                        'seat_section' => $seatData['seat_section'] ?? null,
                        'holder_name' => $customerDetails['name'] ?? $booking->user->full_name,
                        'holder_email' => $customerDetails['email'] ?? $booking->user->email,
                        'holder_phone' => $customerDetails['phone'] ?? $booking->user->phone,
                        'assigned_to' => $booking->user_id,
                        'status' => 'valid',
                        'valid_from' => $event->event_date->subHours(4), // Valid 4 hours before event
                        'valid_until' => $event->end_date ?? $event->event_date->addDay(),
                        'special_requirements' => $customerDetails['special_requirements'] ?? null,
                        'metadata' => [
                            'booking_reference' => $booking->booking_reference,
                            'purchase_date' => now()->toIso8601String(),
                            'event_title' => $event->title,
                            'venue' => $event->venue_name,
                            'address' => $event->venue_address,
                        ],
                    ]);
                    
                    // Generate QR code with security signature
                    $qrData = $this->qrCodeService->generateTicketQrCode($ticket);
                    
                    // Store QR data
                    $ticket->update([
                        'qr_code' => $qrData['qr_code_url'],
                        'ticket_hash' => $qrData['ticket_hash'],
                    ]);
                    
                    $createdTickets[] = $ticket;
                    $ticketCounter++;
                }
            }
            
            // Update booking with ticket count
            $booking->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
            ]);
            
            // Update event ticket sold count
            $event->increment('tickets_sold', count($createdTickets));
            
            DB::commit();
            
            return [
                'success' => true,
                'tickets' => $createdTickets,
                'count' => count($createdTickets),
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            throw new \Exception('Failed to create tickets: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate unique ticket code
     */
    private function generateTicketCode(Event $event, Booking $booking): string
    {
        $prefix = strtoupper(substr($event->slug, 0, 3));
        $date = $event->event_date->format('md');
        $random = strtoupper(Str::random(6));
        
        return sprintf('%s-%s-%s', $prefix, $date, $random);
    }
    
    /**
     * Assign seat if event has seating
     */
    private function assignSeat(Event $event, string $ticketType, int $ticketNumber): array
    {
        // Check if event has seating configuration
        $seatingConfig = $event->gates_config['seating'] ?? null;
        
        if (!$seatingConfig) {
            return [];
        }
        
        // For VIP tickets, assign VIP section
        if (str_contains(strtolower($ticketType), 'vip')) {
            return [
                'seat_section' => 'VIP',
                'seat_number' => 'V' . str_pad($ticketNumber, 3, '0', STR_PAD_LEFT),
            ];
        }
        
        // For regular tickets, assign general section
        return [
            'seat_section' => 'General',
            'seat_number' => 'G' . str_pad($ticketNumber, 4, '0', STR_PAD_LEFT),
        ];
    }
    
    /**
     * Transfer ticket to another user
     */
    public function transferTicket(Ticket $ticket, User $fromUser, User $toUser, ?string $reason = null): bool
    {
        if ($ticket->assigned_to !== $fromUser->id) {
            throw new \Exception('You do not own this ticket');
        }
        
        if ($ticket->status !== 'valid') {
            throw new \Exception('This ticket cannot be transferred');
        }
        
        // Check if ticket is transferable
        $ticketTypeConfig = collect($ticket->event->ticket_types)
            ->firstWhere('name', $ticket->ticket_type);
        
        if ($ticketTypeConfig && !($ticketTypeConfig['transferable'] ?? true)) {
            throw new \Exception('This ticket type is not transferable');
        }
        
        DB::beginTransaction();
        
        try {
            $ticket->transferTo($toUser->id, $fromUser->id, $reason);
            
            // Update holder information
            $ticket->update([
                'holder_name' => $toUser->full_name,
                'holder_email' => $toUser->email,
                'holder_phone' => $toUser->phone,
            ]);
            
            // Regenerate QR code with new holder
            $this->qrCodeService->generateTicketQrCode($ticket);
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Cancel tickets for a booking
     */
    public function cancelTicketsForBooking(Booking $booking): bool
    {
        DB::beginTransaction();
        
        try {
            // Cancel all tickets
            $booking->tickets()->update([
                'status' => 'cancelled',
            ]);
            
            // Update event ticket count
            $cancelledCount = $booking->tickets()->count();
            $booking->event->decrement('tickets_sold', $cancelledCount);
            
            DB::commit();
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Get ticket with full details for display
     */
    public function getTicketDetails(string $ticketId): ?Ticket
    {
        return Ticket::with([
            'event:id,title,venue_name,venue_address,event_date,end_date',
            'booking:id,booking_reference,total_amount,currency',
            'assignedTo:id,full_name,email',
        ])->find($ticketId);
    }
    
    /**
     * Batch generate tickets for multiple bookings
     */
    public function batchGenerateTickets(array $bookingIds): array
    {
        $results = [];
        
        foreach ($bookingIds as $bookingId) {
            try {
                $booking = Booking::find($bookingId);
                if ($booking && $booking->status === 'pending') {
                    $result = $this->createTicketsForBooking($booking);
                    $results[$bookingId] = $result;
                }
            } catch (\Exception $e) {
                $results[$bookingId] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }
}