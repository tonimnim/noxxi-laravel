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

        // 1. Allow multiple bookings per user per event
        // Users may want to buy additional tickets for friends/family
        // Commented out to allow multiple bookings
        // $existingBooking = Booking::where('user_id', $user->id)
        //     ->where('event_id', $event->id)
        //     ->where('status', 'confirmed')
        //     ->where('payment_status', 'paid')
        //     ->first();
        //     
        // if ($existingBooking) {
        //     $errors[] = "You already have a confirmed booking for this event. Reference: {$existingBooking->booking_reference}";
        // }

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

        // Check if event has started or ended based on listing type
        if ($event->listing_type === 'service') {
            // Services are always bookable unless they have an expiry date
            if ($event->end_date && $event->end_date < $now) {
                $errors[] = 'This service is no longer available';
            }
        } elseif ($event->listing_type === 'recurring') {
            // Recurring events: check next occurrence or end date
            if ($event->end_date && $event->end_date < $now) {
                $errors[] = 'This recurring event series has ended';
            }
            // TODO: Add logic for next occurrence checking
        } else {
            // Regular events: check if they have started or ended
            if ($event->end_date) {
                // Multi-day event: check if it has ended
                if ($event->end_date < $now) {
                    $errors[] = 'This event has already ended';
                }
            } elseif ($event->event_date) {
                // Single-day event: check if event date has passed
                if ($event->event_date < $now) {
                    $errors[] = 'This event has already passed';
                }
            } else {
                // Event with no dates set - shouldn't happen
                $errors[] = 'Event dates are not properly configured';
            }
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
     * Get current ticket count (only confirmed tickets)
     * No longer counts pending bookings since they're in cache
     */
    private function getCurrentTicketCount(Event $event): int
    {
        // Count only actual tickets that have been created
        return $event->tickets()
            ->whereIn('status', ['valid', 'used', 'transferred'])
            ->count();
    }

    /**
     * Get available quantity for a specific ticket type
     * Only considers actual sold tickets
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

        return max(0, $totalQuantity - $soldCount);
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
