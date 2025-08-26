<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;

class TicketTypeValidator
{
    /**
     * Validate ticket types from user input against event configuration
     * and return validated ticket data with server-side prices
     */
    public function validateAndPrepareTickets(Event $event, array $requestedTickets): array
    {
        $eventTicketTypes = collect($event->ticket_types ?? []);
        $validatedTickets = [];
        $errors = [];
        $totalQuantity = 0;
        $subtotal = 0;

        foreach ($requestedTickets as $index => $requestedTicket) {
            // Find matching ticket type in event configuration
            $eventTicketType = $eventTicketTypes->firstWhere('name', $requestedTicket['type'] ?? $requestedTicket['name'] ?? '');

            if (! $eventTicketType) {
                $errors[] = 'Invalid ticket type: '.($requestedTicket['type'] ?? $requestedTicket['name'] ?? 'unknown');

                continue;
            }

            // Validate quantity
            $requestedQuantity = (int) ($requestedTicket['quantity'] ?? 1);
            if ($requestedQuantity < 1) {
                $errors[] = "Invalid quantity for {$eventTicketType['name']}";

                continue;
            }

            // Check max per order
            $maxPerOrder = $eventTicketType['max_per_order'] ?? 10;
            if ($requestedQuantity > $maxPerOrder) {
                $errors[] = "{$eventTicketType['name']}: Maximum {$maxPerOrder} tickets per order";

                continue;
            }

            // Check sale dates
            $now = now();
            if (! empty($eventTicketType['sale_start'])) {
                $saleStart = Carbon::parse($eventTicketType['sale_start']);
                if ($now->lt($saleStart)) {
                    $errors[] = "{$eventTicketType['name']}: Sales haven't started yet";

                    continue;
                }
            }

            if (! empty($eventTicketType['sale_end'])) {
                $saleEnd = Carbon::parse($eventTicketType['sale_end']);
                if ($now->gt($saleEnd)) {
                    $errors[] = "{$eventTicketType['name']}: Sales have ended";

                    continue;
                }
            }

            // Check availability
            $availableQuantity = $this->getAvailableQuantity($event, $eventTicketType);
            if ($requestedQuantity > $availableQuantity) {
                $errors[] = "{$eventTicketType['name']}: Only {$availableQuantity} tickets available";

                continue;
            }

            // Use server-side price (NEVER trust client price)
            $price = (float) ($eventTicketType['price'] ?? 0);
            $ticketSubtotal = $price * $requestedQuantity;

            $validatedTickets[] = [
                'name' => $eventTicketType['name'],
                'type' => $eventTicketType['name'], // For backward compatibility
                'price' => $price,
                'quantity' => $requestedQuantity,
                'subtotal' => $ticketSubtotal,
                'description' => $eventTicketType['description'] ?? null,
                'transferable' => $eventTicketType['transferable'] ?? true,
                'refundable' => $eventTicketType['refundable'] ?? false,
            ];

            $totalQuantity += $requestedQuantity;
            $subtotal += $ticketSubtotal;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'tickets' => $validatedTickets,
            'total_quantity' => $totalQuantity,
            'subtotal' => $subtotal,
            'currency' => $event->currency, // Always use event's currency
        ];
    }

    /**
     * Get available quantity for a ticket type
     */
    private function getAvailableQuantity(Event $event, array $ticketType): int
    {
        // Get total quantity configured for this ticket type
        $totalQuantity = (int) ($ticketType['quantity'] ?? 100);

        // Calculate sold tickets for this type
        $soldCount = $event->tickets()
            ->where('ticket_type', $ticketType['name'])
            ->whereIn('status', ['valid', 'used', 'transferred'])
            ->count();

        // Include pending bookings (tickets not yet created but booking exists)
        $pendingCount = \DB::table('bookings')
            ->where('event_id', $event->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereIn('payment_status', ['processing', 'paid'])
            ->get()
            ->sum(function ($booking) use ($ticketType) {
                $ticketTypes = json_decode($booking->ticket_types, true) ?? [];
                $count = 0;
                foreach ($ticketTypes as $type) {
                    if (($type['name'] ?? $type['type'] ?? '') === $ticketType['name']) {
                        $count += (int) ($type['quantity'] ?? 0);
                    }
                }

                return $count;
            });

        return max(0, $totalQuantity - $soldCount - $pendingCount);
    }

    /**
     * Calculate service fee based on event or organizer settings
     */
    public function calculateServiceFee(Event $event, float $subtotal): float
    {
        // Check if event has specific platform fee (0 means use organizer's rate)
        if ($event->platform_fee !== null && $event->platform_fee != 0) {
            return round($subtotal * ($event->platform_fee / 100), 2);
        }

        // Use organizer's commission rate
        $organizer = $event->organizer;
        if ($organizer && $organizer->commission_rate > 0) {
            return round($subtotal * ($organizer->commission_rate / 100), 2);
        }

        // Default to 3% if nothing is configured
        return round($subtotal * 0.03, 2);
    }

    /**
     * Validate if event is available for booking
     */
    public function validateEventAvailability(Event $event): array
    {
        $errors = [];

        // Check if event is published
        if ($event->status !== 'published') {
            $errors[] = 'This event is not available for booking';
        }

        // Check if event date has passed
        if ($event->event_date < now()) {
            $errors[] = 'This event has already passed';
        }

        // Check if event is cancelled
        if ($event->status === 'cancelled') {
            $errors[] = 'This event has been cancelled';
        }

        // Check if event is paused
        if ($event->status === 'paused') {
            $errors[] = 'Ticket sales are currently paused for this event';
        }

        // Check overall capacity
        $totalSold = $event->tickets()
            ->whereIn('status', ['valid', 'used', 'transferred'])
            ->count();

        if ($totalSold >= $event->capacity) {
            $errors[] = 'This event is sold out';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get ticket type by name from event
     */
    public function getTicketTypeByName(Event $event, string $typeName): ?array
    {
        $ticketTypes = collect($event->ticket_types ?? []);

        return $ticketTypes->firstWhere('name', $typeName);
    }
}
