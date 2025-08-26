<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

class BookingValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $anotherUser;

    protected Organizer $organizer;

    protected Event $event;

    protected EventCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->user = User::factory()->create();
        $this->anotherUser = User::factory()->create();

        // Create organizer
        $organizerUser = User::factory()->create();
        $this->organizer = Organizer::factory()->create([
            'user_id' => $organizerUser->id,
            'default_currency' => 'KES',
            'commission_rate' => 10,
        ]);

        // Create category
        $this->category = EventCategory::firstOrCreate(
            ['slug' => 'events'],
            [
                'name' => 'Events',
                'parent_id' => null,
                'description' => 'General events category',
                'icon' => 'calendar',
                'color' => '#0000FF',
                'is_active' => true,
                'sort_order' => 1,
                'metadata' => [],
            ]
        );

        // Create event
        $this->event = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'category_id' => $this->category->id,
            'title' => 'Test Concert',
            'currency' => 'KES',
            'status' => 'published',
            'event_date' => now()->addDays(30),
            'capacity' => 100,
            'ticket_types' => [
                [
                    'name' => 'Regular',
                    'price' => 1000,
                    'quantity' => 50,
                    'description' => 'Regular ticket',
                    'max_per_order' => 10,
                    'sale_start' => now()->subDay()->format('Y-m-d H:i:s'),
                    'sale_end' => now()->addDays(29)->format('Y-m-d H:i:s'),
                ],
                [
                    'name' => 'VIP',
                    'price' => 5000,
                    'quantity' => 20,
                    'description' => 'VIP ticket',
                    'max_per_order' => 5,
                    'sale_start' => now()->addDays(5)->format('Y-m-d H:i:s'), // Future start
                    'sale_end' => now()->addDays(29)->format('Y-m-d H:i:s'),
                ],
                [
                    'name' => 'Early Bird',
                    'price' => 800,
                    'quantity' => 30,
                    'description' => 'Early bird special',
                    'max_per_order' => 5,
                    'sale_start' => now()->subDays(10)->format('Y-m-d H:i:s'),
                    'sale_end' => now()->subDays(2)->format('Y-m-d H:i:s'), // Already ended
                ],
            ],
        ]);

        // Authenticate user
        Passport::actingAs($this->user, ['*']);
    }

    /**
     * Test duplicate booking prevention
     */
    public function test_prevents_duplicate_bookings()
    {
        // Create first booking
        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Regular', 'quantity' => 2],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(201);

        // Try to create duplicate booking
        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Regular', 'quantity' => 1],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('errors.0', 'You have a pending booking for this event. Please complete or cancel it first. Booking reference: '.Booking::first()->booking_reference);
    }

    /**
     * Test that abandoned bookings can be replaced
     */
    public function test_abandoned_bookings_can_be_replaced()
    {
        // Create a booking and make it old (abandoned)
        $oldBooking = Booking::create([
            'user_id' => $this->user->id,
            'event_id' => $this->event->id,
            'booking_reference' => 'BK'.strtoupper(\Str::random(8)),
            'ticket_quantity' => 1,
            'ticket_types' => [['name' => 'Regular', 'quantity' => 1]],
            'subtotal' => 1000,
            'service_fee' => 100,
            'total_amount' => 1100,
            'currency' => 'KES',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'customer_phone' => '',
        ]);

        // Force the created_at to be old
        DB::table('bookings')
            ->where('id', $oldBooking->id)
            ->update(['created_at' => now()->subHours(1)]);

        // Try to create new booking (should cancel old one)
        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Regular', 'quantity' => 2],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(201);

        // Check old booking was cancelled
        $oldBooking->refresh();
        $this->assertEquals('cancelled', $oldBooking->status);
    }

    /**
     * Test unpublished events cannot be booked
     */
    public function test_unpublished_events_cannot_be_booked()
    {
        $this->event->update(['status' => 'draft']);

        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Regular', 'quantity' => 1],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('errors.0', 'This event is not available for booking yet');
    }

    /**
     * Test cancelled events cannot be booked
     */
    public function test_cancelled_events_cannot_be_booked()
    {
        $this->event->update(['status' => 'cancelled']);

        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Regular', 'quantity' => 1],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('errors.0', 'This event has been cancelled');
    }

    /**
     * Test paused events cannot be booked
     */
    public function test_paused_events_cannot_be_booked()
    {
        $this->event->update(['status' => 'paused']);

        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Regular', 'quantity' => 1],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('errors.0', 'Ticket sales are temporarily paused for this event');
    }

    /**
     * Test ticket sale period validation - before sale starts
     */
    public function test_cannot_book_ticket_before_sale_starts()
    {
        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'VIP', 'quantity' => 1], // VIP sales start in 5 days
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        // Check for the error message without the specific date
        $this->assertStringContainsString('VIP: Sales haven\'t started yet', json_encode($response->json('errors')));
    }

    /**
     * Test ticket sale period validation - after sale ends
     */
    public function test_cannot_book_ticket_after_sale_ends()
    {
        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Early Bird', 'quantity' => 1], // Early Bird sales ended 2 days ago
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        // Check for the error message without the specific date
        $this->assertStringContainsString('Early Bird: Sales have ended', json_encode($response->json('errors')));
    }

    /**
     * Test capacity validation
     */
    public function test_cannot_exceed_event_capacity()
    {
        // Update event to have low capacity
        $this->event->update(['capacity' => 5]);

        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Regular', 'quantity' => 10],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['Only 5 tickets available, but you requested 10']);
    }

    /**
     * Test sold out event
     */
    public function test_cannot_book_sold_out_event()
    {
        // Mark event as sold out
        $this->event->update([
            'capacity' => 50,
            'tickets_sold' => 50,
        ]);

        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Regular', 'quantity' => 1],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('errors.0', 'This event is sold out');
    }

    /**
     * Test that different users can book the same event
     */
    public function test_different_users_can_book_same_event()
    {
        // First user books
        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Regular', 'quantity' => 2],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(201);

        // Second user books
        Passport::actingAs($this->anotherUser, ['*']);

        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Regular', 'quantity' => 3],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(201);

        // Verify both bookings exist
        $this->assertEquals(2, Booking::count());
    }

    /**
     * Test confirmed booking prevents duplicate
     */
    public function test_confirmed_booking_prevents_duplicate()
    {
        // Create confirmed booking
        Booking::create([
            'user_id' => $this->user->id,
            'event_id' => $this->event->id,
            'booking_reference' => 'BK'.strtoupper(\Str::random(8)),
            'ticket_quantity' => 2,
            'ticket_types' => [['name' => 'Regular', 'quantity' => 2]],
            'subtotal' => 2000,
            'service_fee' => 200,
            'total_amount' => 2200,
            'currency' => 'KES',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'customer_phone' => '',
        ]);

        // Try to create another booking
        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Regular', 'quantity' => 1],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        // Check for the error message with dynamic booking reference
        $this->assertStringContainsString('You already have a booking for this event', json_encode($response->json('errors')));
    }

    /**
     * Test that ticket type sold out is detected
     */
    public function test_ticket_type_sold_out_detection()
    {
        // Create a limited ticket type event
        $limitedEvent = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'category_id' => $this->category->id,
            'currency' => 'KES',
            'status' => 'published',
            'event_date' => now()->addDays(30),
            'capacity' => 10,
            'ticket_types' => [
                [
                    'name' => 'Limited',
                    'price' => 1000,
                    'quantity' => 2, // Only 2 available
                    'max_per_order' => 10,
                    'sale_start' => now()->subDay()->format('Y-m-d H:i:s'),
                    'sale_end' => now()->addDays(29)->format('Y-m-d H:i:s'),
                ],
            ],
        ]);

        // First user books all available
        $response = $this->postJson('/api/bookings', [
            'event_id' => $limitedEvent->id,
            'ticket_types' => [
                ['name' => 'Limited', 'quantity' => 2],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(201);

        // Second user tries to book
        Passport::actingAs($this->anotherUser, ['*']);

        $response = $this->postJson('/api/bookings', [
            'event_id' => $limitedEvent->id,
            'ticket_types' => [
                ['name' => 'Limited', 'quantity' => 1],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        // Should show sold out error
        $response->assertJson(['success' => false]);
        $this->assertNotEmpty($response->json('errors'));
    }
}
