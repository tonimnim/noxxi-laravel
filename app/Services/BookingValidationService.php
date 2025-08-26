<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingValidationService
{
    /**
     * Comprehensive validation for new bookings
     *
     * @return array ['valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public function validateBookingRequest(User $user, Event $event, array $ticketTypes): array
    {
        $errors = [];
        $warnings = [];

        // 1. Check for duplicate bookings (user already has active booking for this event)
        $duplicateCheck = $this->checkDuplicateBooking($user, $event);
        if (! $duplicateCheck['valid']) {
            $errors = array_merge($errors, $duplicateCheck['errors']);
        }

        // 2. Validate event availability and status
        $eventCheck = $this->validateEventStatus($event);
        if (! $eventCheck['valid']) {
            $errors = array_merge($errors, $eventCheck['errors']);
        }

        // 3. Check overall event capacity
        $capacityCheck = $this->validateEventCapacity($event, $ticketTypes);
        if (! $capacityCheck['valid']) {
            $errors = array_merge($errors, $capacityCheck['errors']);
        }

        // 4. Validate ticket sale periods for each ticket type
        $salePeriodsCheck = $this->validateTicketSalePeriods($event, $ticketTypes);
        if (! $salePeriodsCheck['valid']) {
            $errors = array_merge($errors, $salePeriodsCheck['errors']);
        }

        // 5. Check if event is almost sold out (warning, not error)
        if ($event->available_tickets < 10 && $event->available_tickets > 0) {
            $warnings[] = "Only {$event->available_tickets} tickets remaining!";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Check if user already has an active booking for this event
     * Prevents duplicate bookings
     */
    public function checkDuplicateBooking(User $user, Event $event): array
    {
        // Check for existing pending or confirmed bookings
        $existingBooking = Booking::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereIn('payment_status', ['unpaid', 'processing', 'paid'])
            ->first();

        if ($existingBooking) {
            $status = $existingBooking->status;
            $paymentStatus = $existingBooking->payment_status;

            // If booking is pending and unpaid, it might be abandoned
            if ($status === 'pending' && $paymentStatus === 'unpaid') {
                // Check if booking is older than 30 minutes (abandoned)
                if ($existingBooking->created_at->diffInMinutes(now()) > 30) {
                    // Cancel the old booking
                    $existingBooking->cancel('Abandoned - new booking initiated');

                    return ['valid' => true, 'errors' => []];
                }

                return [
                    'valid' => false,
                    'errors' => ["You have a pending booking for this event. Please complete or cancel it first. Booking reference: {$existingBooking->booking_reference}"],
                ];
            }

            return [
                'valid' => false,
                'errors' => ["You already have a booking for this event. Booking reference: {$existingBooking->booking_reference}"],
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    /**
     * Validate event status and availability
     */
    public function validateEventStatus(Event $event): array
    {
        $errors = [];
        $now = now();

        // Check if event is published
        if ($event->status !== 'published') {
            switch ($event->status) {
                case 'draft':
                    $errors[] = 'This event is not available for booking yet';
                    break;
                case 'cancelled':
                    $errors[] = 'This event has been cancelled';
                    break;
                case 'paused':
                    $errors[] = 'Ticket sales are temporarily paused for this event';
                    break;
                case 'completed':
                    $errors[] = 'This event has already taken place';
                    break;
                default:
                    $errors[] = 'This event is not available for booking';
            }
        }

        // Check if event date has passed
        if ($event->event_date < $now) {
            $errors[] = 'This event has already passed';
        }

        // Check if event is sold out
        if ($event->isSoldOut()) {
            $errors[] = 'This event is sold out';
        }

        // Check if event requires approval
        if ($event->requires_approval) {
            $errors[] = 'This event requires manual approval for bookings';
        }

        // Check age restriction
        // This would need user's age to be stored, skipping for now
        // if ($event->age_restriction > 0 && $user->age < $event->age_restriction) {
        //     $errors[] = "This event requires attendees to be at least {$event->age_restriction} years old";
        // }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate event has enough capacity for requested tickets
     */
    public function validateEventCapacity(Event $event, array $ticketTypes): array
    {
        $errors = [];

        // Calculate total requested quantity
        $totalRequested = array_reduce($ticketTypes, function ($carry, $ticket) {
            return $carry + ($ticket['quantity'] ?? 0);
        }, 0);

        // Get current ticket count (sold + pending)
        $currentTickets = $this->getCurrentTicketCount($event);

        // Check if adding these tickets would exceed capacity
        if (($currentTickets + $totalRequested) > $event->capacity) {
            $available = max(0, $event->capacity - $currentTickets);
            if ($available === 0) {
                $errors[] = 'This event is sold out';
            } else {
                $errors[] = "Only {$available} tickets available, but you requested {$totalRequested}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate ticket sale periods for each ticket type
     */
    public function validateTicketSalePeriods(Event $event, array $requestedTickets): array
    {
        $errors = [];
        $now = now();
        $eventTicketTypes = collect($event->ticket_types ?? []);

        foreach ($requestedTickets as $requested) {
            $ticketName = $requested['name'] ?? $requested['type'] ?? '';
            $ticketType = $eventTicketTypes->firstWhere('name', $ticketName);

            if (! $ticketType) {
                continue; // This error is handled elsewhere
            }

            // Check sale start date
            if (! empty($ticketType['sale_start'])) {
                $saleStart = Carbon::parse($ticketType['sale_start']);
                if ($now < $saleStart) {
                    $errors[] = "{$ticketName}: Sales haven't started yet. Sales begin ".$saleStart->format('M d, Y H:i');
                }
            }

            // Check sale end date
            if (! empty($ticketType['sale_end'])) {
                $saleEnd = Carbon::parse($ticketType['sale_end']);
                if ($now > $saleEnd) {
                    $errors[] = "{$ticketName}: Sales have ended. Sales ended ".$saleEnd->format('M d, Y H:i');
                }
            }

            // Check if this specific ticket type is sold out
            $availableForType = $this->getAvailableQuantityForTicketType($event, $ticketType);
            $requestedQuantity = (int) ($requested['quantity'] ?? 0);

            if ($requestedQuantity > $availableForType) {
                if ($availableForType === 0) {
                    $errors[] = "{$ticketName}: Sold out";
                } else {
                    $errors[] = "{$ticketName}: Only {$availableForType} tickets available";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get current ticket count including confirmed and pending bookings
     * Uses optimized query to prevent N+1 issues
     */
    private function getCurrentTicketCount(Event $event): int
    {
        // Count actual tickets
        $ticketCount = $event->tickets()
            ->whereIn('status', ['valid', 'used', 'transferred'])
            ->count();

        // Count pending bookings (tickets not yet created)
        // Use ticket_quantity if available, otherwise sum from ticket_types JSON
        $pendingBookings = DB::table('bookings')
            ->where('event_id', $event->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereIn('payment_status', ['unpaid', 'processing', 'paid'])
            ->where('created_at', '>', now()->subMinutes(30)) // Only count recent pending bookings
            ->get();

        $pendingTickets = 0;
        foreach ($pendingBookings as $booking) {
            // Use ticket_quantity if set, otherwise calculate from ticket_types
            if ($booking->ticket_quantity) {
                $pendingTickets += $booking->ticket_quantity;
            } else {
                $ticketTypes = json_decode($booking->ticket_types, true) ?? [];
                foreach ($ticketTypes as $type) {
                    $pendingTickets += (int) ($type['quantity'] ?? 0);
                }
            }
        }

        return $ticketCount + $pendingTickets;
    }

    /**
     * Get available quantity for a specific ticket type
     * Considers both created tickets and pending bookings
     */
    private function getAvailableQuantityForTicketType(Event $event, array $ticketType): int
    {
        $totalQuantity = (int) ($ticketType['quantity'] ?? 100);
        $typeName = $ticketType['name'];

        // Count sold tickets for this type
        $soldCount = $event->tickets()
            ->where('ticket_type', $typeName)
            ->whereIn('status', ['valid', 'used', 'transferred'])
            ->count();

        // Count pending bookings for this ticket type (simpler approach for compatibility)
        $pendingBookings = DB::table('bookings')
            ->where('event_id', $event->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereIn('payment_status', ['unpaid', 'processing', 'paid'])
            ->where('created_at', '>', now()->subMinutes(30)) // Only recent pending bookings
            ->get();

        $pendingCount = 0;
        foreach ($pendingBookings as $booking) {
            $ticketTypes = json_decode($booking->ticket_types, true) ?? [];
            foreach ($ticketTypes as $type) {
                if (($type['name'] ?? $type['type'] ?? '') === $typeName) {
                    $pendingCount += (int) ($type['quantity'] ?? 0);
                }
            }
        }

        return max(0, $totalQuantity - $soldCount - $pendingCount);
    }

    /**
     * Lock and validate ticket availability in a transaction
     * This prevents race conditions during concurrent bookings
     */
    public function validateWithLocking(Event $event, array $ticketTypes, callable $callback)
    {
        return DB::transaction(function () use ($event, $ticketTypes, $callback) {
            // Lock the event record
            $lockedEvent = Event::lockForUpdate()->findOrFail($event->id);

            // Re-validate capacity with locked data
            $capacityCheck = $this->validateEventCapacity($lockedEvent, $ticketTypes);
            if (! $capacityCheck['valid']) {
                throw new \Exception(implode(', ', $capacityCheck['errors']));
            }

            // Execute the callback (create booking, etc.)
            return $callback($lockedEvent);
        }, 5); // 5 second timeout
    }

    /**
     * Check if a booking can be cancelled
     */
    public function canCancelBooking(Booking $booking): array
    {
        $errors = [];

        // Check if already cancelled
        if ($booking->status === 'cancelled') {
            $errors[] = 'This booking is already cancelled';
        }

        // Check if already refunded
        if ($booking->status === 'refunded') {
            $errors[] = 'This booking has been refunded and cannot be cancelled';
        }

        // Check if event has already happened
        if ($booking->event->isPast()) {
            $errors[] = 'Cannot cancel bookings for past events';
        }

        // Check if tickets have been used
        if ($booking->hasCheckedInTickets()) {
            $errors[] = 'Cannot cancel booking with checked-in tickets';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
