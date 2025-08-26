<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Str;

class TicketPdfService
{
    /**
     * Generate a simple HTML representation for the ticket
     * This can be converted to PDF by the frontend or a PDF library
     */
    public function generateTicketHtml(Ticket $ticket): string
    {
        $event = $ticket->event;
        $booking = $ticket->booking;

        // Load the ticket template
        return view('pdfs.ticket', [
            'ticket' => $ticket,
            'event' => $event,
            'booking' => $booking,
            'qrCodeUrl' => $ticket->qr_code,
            'ticketData' => [
                'code' => $ticket->ticket_code,
                'type' => $ticket->ticket_type,
                'holder_name' => $ticket->holder_name,
                'holder_email' => $ticket->holder_email,
                'seat_number' => $ticket->seat_number,
                'seat_section' => $ticket->seat_section,
            ],
            'eventData' => [
                'title' => $event->title,
                'venue' => $event->venue_name,
                'address' => $event->venue_address,
                'city' => $event->city,
                'date' => $event->event_date->format('l, F j, Y'),
                'time' => $event->event_date->format('g:i A'),
                'organizer' => $event->organizer->business_name,
            ],
            'validityData' => [
                'valid_from' => $ticket->valid_from?->format('F j, Y g:i A'),
                'valid_until' => $ticket->valid_until?->format('F j, Y g:i A'),
            ],
        ])->render();
    }

    /**
     * Generate ticket data for client-side PDF generation
     * Optimized for mobile apps to generate PDFs locally
     */
    public function getTicketPdfData(Ticket $ticket): array
    {
        $event = $ticket->event;
        $booking = $ticket->booking;

        return [
            'ticket' => [
                'id' => $ticket->id,
                'code' => $ticket->ticket_code,
                'type' => $ticket->ticket_type,
                'price' => $ticket->price,
                'currency' => $ticket->currency,
                'holder_name' => $ticket->holder_name,
                'holder_email' => $ticket->holder_email,
                'holder_phone' => $ticket->holder_phone,
                'seat_number' => $ticket->seat_number,
                'seat_section' => $ticket->seat_section,
                'status' => $ticket->status,
                'qr_code' => $ticket->qr_code,
                'valid_from' => $ticket->valid_from?->toIso8601String(),
                'valid_until' => $ticket->valid_until?->toIso8601String(),
            ],
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => Str::limit($event->description, 200),
                'venue_name' => $event->venue_name,
                'venue_address' => $event->venue_address,
                'city' => $event->city,
                'event_date' => $event->event_date->toIso8601String(),
                'event_date_formatted' => $event->event_date->format('l, F j, Y'),
                'event_time_formatted' => $event->event_date->format('g:i A'),
                'end_date' => $event->end_date?->toIso8601String(),
                'cover_image' => $event->cover_image_url,
                'organizer_name' => $event->organizer->business_name,
                'organizer_email' => $event->organizer->user->email,
                'organizer_phone' => $event->organizer->contact_phone,
            ],
            'booking' => [
                'reference' => $booking->booking_reference,
                'total_amount' => $booking->total_amount,
                'currency' => $booking->currency,
                'created_at' => $booking->created_at->toIso8601String(),
            ],
            'instructions' => [
                'arrival' => 'Please arrive at least 30 minutes before the event starts.',
                'entry' => 'Present this ticket QR code at the entrance for scanning.',
                'support' => 'For support, contact the organizer or visit noxxi.com/support',
            ],
            'terms' => [
                'transferable' => $this->isTicketTransferable($ticket),
                'refundable' => $this->isTicketRefundable($ticket),
                'validity' => 'This ticket is valid only for the specified date and time.',
            ],
        ];
    }

    /**
     * Generate batch tickets data for multiple tickets
     */
    public function getBatchTicketsPdfData(array $ticketIds): array
    {
        $tickets = Ticket::whereIn('id', $ticketIds)
            ->with(['event', 'booking'])
            ->get();

        return $tickets->map(function ($ticket) {
            return $this->getTicketPdfData($ticket);
        })->toArray();
    }

    /**
     * Generate a downloadable ticket filename
     */
    public function generateTicketFilename(Ticket $ticket): string
    {
        $eventSlug = Str::slug($ticket->event->title);
        $date = $ticket->event->event_date->format('Y-m-d');

        return sprintf(
            'ticket_%s_%s_%s.pdf',
            $eventSlug,
            $date,
            $ticket->ticket_code
        );
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

    /**
     * Check if ticket is refundable
     */
    private function isTicketRefundable(Ticket $ticket): bool
    {
        // Check event refund policy
        $refundPolicy = $ticket->event->policies['refund_policy'] ?? 'no_refunds';

        if ($refundPolicy === 'no_refunds') {
            return false;
        }

        // Check if event date has passed
        if ($ticket->event->event_date->isPast()) {
            return false;
        }

        // Check refund deadline if specified
        if ($refundPolicy === 'before_event') {
            $refundDeadline = $ticket->event->policies['refund_deadline_hours'] ?? 24;
            $deadlineTime = $ticket->event->event_date->subHours($refundDeadline);

            return now()->isBefore($deadlineTime);
        }

        return true;
    }

    /**
     * Generate Apple Wallet pass data
     */
    public function getAppleWalletPassData(Ticket $ticket): array
    {
        $event = $ticket->event;

        return [
            'description' => $event->title,
            'formatVersion' => 1,
            'organizationName' => 'Noxxi',
            'passTypeIdentifier' => 'pass.com.noxxi.tickets',
            'serialNumber' => $ticket->ticket_code,
            'teamIdentifier' => config('services.apple.team_id'),
            'eventTicket' => [
                'primaryFields' => [
                    [
                        'key' => 'event',
                        'label' => 'EVENT',
                        'value' => $event->title,
                    ],
                ],
                'secondaryFields' => [
                    [
                        'key' => 'location',
                        'label' => 'VENUE',
                        'value' => $event->venue_name,
                    ],
                    [
                        'key' => 'date',
                        'label' => 'DATE',
                        'value' => $event->event_date->format('M j, Y'),
                    ],
                ],
                'auxiliaryFields' => [
                    [
                        'key' => 'time',
                        'label' => 'TIME',
                        'value' => $event->event_date->format('g:i A'),
                    ],
                    [
                        'key' => 'ticket_type',
                        'label' => 'TYPE',
                        'value' => $ticket->ticket_type,
                    ],
                ],
                'backFields' => [
                    [
                        'key' => 'ticket_code',
                        'label' => 'Ticket Code',
                        'value' => $ticket->ticket_code,
                    ],
                    [
                        'key' => 'holder_name',
                        'label' => 'Ticket Holder',
                        'value' => $ticket->holder_name,
                    ],
                    [
                        'key' => 'seat',
                        'label' => 'Seat',
                        'value' => $ticket->seat_number ?? 'General Admission',
                    ],
                ],
            ],
            'barcode' => [
                'format' => 'PKBarcodeFormatQR',
                'message' => $ticket->qr_code,
                'messageEncoding' => 'iso-8859-1',
            ],
            'locations' => [
                [
                    'latitude' => $event->latitude ?? 0,
                    'longitude' => $event->longitude ?? 0,
                    'relevantText' => 'Event is nearby',
                ],
            ],
            'relevantDate' => $event->event_date->toIso8601String(),
        ];
    }

    /**
     * Generate Google Wallet object data
     */
    public function getGoogleWalletObjectData(Ticket $ticket): array
    {
        $event = $ticket->event;

        return [
            'id' => $ticket->id,
            'classId' => 'noxxi_event_tickets',
            'logo' => [
                'sourceUri' => [
                    'uri' => asset('images/logo.png'),
                ],
            ],
            'heroImage' => [
                'sourceUri' => [
                    'uri' => $event->cover_image_url,
                ],
            ],
            'textModulesData' => [
                [
                    'header' => 'Ticket Type',
                    'body' => $ticket->ticket_type,
                ],
                [
                    'header' => 'Ticket Holder',
                    'body' => $ticket->holder_name,
                ],
            ],
            'linksModuleData' => [
                'uris' => [
                    [
                        'uri' => config('app.url').'/tickets/'.$ticket->id,
                        'description' => 'View Ticket Details',
                    ],
                ],
            ],
            'barcode' => [
                'type' => 'QR_CODE',
                'value' => $ticket->qr_code,
            ],
            'locations' => [
                [
                    'latitude' => $event->latitude ?? 0,
                    'longitude' => $event->longitude ?? 0,
                ],
            ],
            'validTimeInterval' => [
                'start' => [
                    'date' => $ticket->valid_from?->toIso8601String(),
                ],
                'end' => [
                    'date' => $ticket->valid_until?->toIso8601String(),
                ],
            ],
        ];
    }
}
