<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class BookingSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Organizer $organizer;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user
        $this->user = User::factory()->create();

        // Create organizer
        $organizerUser = User::factory()->create();
        $this->organizer = Organizer::factory()->create([
            'user_id' => $organizerUser->id,
            'default_currency' => 'KES',
            'commission_rate' => 10,
        ]);

        // Create event with specific ticket types
        // Create or get a category for testing
        $category = \App\Models\EventCategory::firstOrCreate(
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

        $this->event = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'category_id' => $category->id,
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
                    'transferable' => true,
                    'refundable' => false,
                ],
                [
                    'name' => 'VIP',
                    'price' => 5000,
                    'quantity' => 20,
                    'description' => 'VIP ticket with backstage access',
                    'max_per_order' => 5,
                    'transferable' => true,
                    'refundable' => true,
                ],
            ],
        ]);

        // Authenticate user
        Passport::actingAs($this->user, ['*']);
    }

    /**
     * Test that client cannot manipulate ticket prices
     */
    public function test_client_cannot_manipulate_ticket_prices()
    {
        // Attempt to book with manipulated price (trying to pay 100 instead of 1000)
        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                [
                    'name' => 'Regular',
                    'quantity' => 2,
                    'price' => 100, // Wrong price! Should be ignored
                ],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(201);

        // Verify that server-side price was used
        $booking = \App\Models\Booking::latest()->first();
        $this->assertEquals(2000, $booking->subtotal); // 2 tickets × 1000 (server price)
        $this->assertNotEquals(200, $booking->subtotal); // Not 2 × 100 (client price)
    }

    /**
     * Test that invalid ticket types are rejected
     */
    public function test_invalid_ticket_types_are_rejected()
    {
        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                [
                    'name' => 'SuperVIP', // Doesn't exist
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid ticket selection',
        ]);
    }

    /**
     * Test that booking uses event's currency
     */
    public function test_booking_uses_event_currency()
    {
        // Get category for testing
        $category = \App\Models\EventCategory::first();

        // Create event with USD currency
        $usdEvent = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'category_id' => $category->id,
            'currency' => 'USD',
            'status' => 'published',
            'event_date' => now()->addDays(30),
            'ticket_types' => [
                [
                    'name' => 'General',
                    'price' => 50,
                    'quantity' => 100,
                ],
            ],
        ]);

        $response = $this->postJson('/api/bookings', [
            'event_id' => $usdEvent->id,
            'ticket_types' => [
                [
                    'name' => 'General',
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.currency', 'USD');

        $booking = \App\Models\Booking::latest()->first();
        $this->assertEquals('USD', $booking->currency);
    }

    /**
     * Test that over-booking is prevented
     */
    public function test_over_booking_is_prevented()
    {
        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                [
                    'name' => 'Regular',
                    'quantity' => 51, // More than available (50)
                ],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * Test that max per order limit is enforced
     */
    public function test_max_per_order_limit_is_enforced()
    {
        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                [
                    'name' => 'Regular',
                    'quantity' => 11, // More than max_per_order (10)
                ],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * Test that unpublished events cannot be booked
     */
    public function test_unpublished_events_cannot_be_booked()
    {
        $this->event->update(['status' => 'draft']);

        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                [
                    'name' => 'Regular',
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Booking validation failed',
        ]);
        $this->assertStringContainsString('This event is not available for booking', json_encode($response->json('errors')));
    }

    /**
     * Test that past events cannot be booked
     */
    public function test_past_events_cannot_be_booked()
    {
        $this->event->update(['event_date' => now()->subDay()]);

        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                [
                    'name' => 'Regular',
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Booking validation failed',
        ]);
        $this->assertStringContainsString('This event has already passed', json_encode($response->json('errors')));
    }

    /**
     * Test that service fee is calculated correctly based on organizer settings
     */
    public function test_service_fee_calculation()
    {
        // Set specific commission rate on organizer
        $this->organizer->update(['commission_rate' => 5]);
        // Set platform_fee to 0 (can't be null due to DB constraint) so it uses organizer's commission
        $this->event->update(['platform_fee' => 0]);

        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                [
                    'name' => 'Regular',
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'card',
        ]);

        $response->assertStatus(201);

        $booking = \App\Models\Booking::latest()->first();
        $this->assertEquals(1000, $booking->subtotal);
        $this->assertEquals(50, $booking->service_fee); // 5% of 1000
        $this->assertEquals(1050, $booking->total_amount);
    }

    /**
     * Test concurrent booking attempts (race condition prevention)
     */
    public function test_concurrent_booking_race_condition_prevention()
    {
        // Get category for testing
        $category = \App\Models\EventCategory::first();

        // Create event with only 2 tickets available
        $limitedEvent = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'category_id' => $category->id,
            'currency' => 'KES',
            'status' => 'published',
            'event_date' => now()->addDays(30),
            'capacity' => 2,
            'ticket_types' => [
                [
                    'name' => 'Limited',
                    'price' => 1000,
                    'quantity' => 2,
                ],
            ],
        ]);

        // Simulate concurrent bookings
        $responses = [];

        // First booking should succeed
        $responses[] = $this->postJson('/api/bookings', [
            'event_id' => $limitedEvent->id,
            'ticket_types' => [
                [
                    'name' => 'Limited',
                    'quantity' => 2,
                ],
            ],
            'payment_method' => 'card',
        ]);

        // Second booking should fail (no tickets left)
        $responses[] = $this->postJson('/api/bookings', [
            'event_id' => $limitedEvent->id,
            'ticket_types' => [
                [
                    'name' => 'Limited',
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'card',
        ]);

        // First should succeed
        $responses[0]->assertStatus(201);

        // Second should fail
        $responses[1]->assertStatus(400);

        // Only one booking should exist
        $bookingCount = \App\Models\Booking::where('event_id', $limitedEvent->id)->count();
        $this->assertEquals(1, $bookingCount);
    }
}
