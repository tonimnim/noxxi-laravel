<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organizer;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TicketManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected Organizer $organizer;

    protected Event $event;

    protected Booking $booking;

    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        // Create organizer
        $organizerUser = User::factory()->create();
        $this->organizer = Organizer::factory()->create([
            'user_id' => $organizerUser->id,
            'default_currency' => 'KES',
        ]);

        // Create category
        $category = EventCategory::firstOrCreate(
            ['slug' => 'concerts'],
            [
                'name' => 'Concerts',
                'parent_id' => null,
                'description' => 'Music concerts and festivals',
                'icon' => 'music',
                'color' => '#FF5733',
                'is_active' => true,
                'sort_order' => 1,
                'metadata' => [],
            ]
        );

        // Create event
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
                    'name' => 'General',
                    'price' => 1000,
                    'quantity' => 50,
                    'transferable' => true,
                ],
                [
                    'name' => 'VIP',
                    'price' => 5000,
                    'quantity' => 20,
                    'transferable' => false,
                ],
            ],
        ]);

        // Create booking
        $this->booking = Booking::create([
            'user_id' => $this->user->id,
            'event_id' => $this->event->id,
            'booking_reference' => 'BK'.strtoupper(\Str::random(8)),
            'ticket_quantity' => 2,
            'ticket_types' => [
                ['name' => 'General', 'quantity' => 1, 'price' => 1000],
                ['name' => 'VIP', 'quantity' => 1, 'price' => 5000],
            ],
            'subtotal' => 6000,
            'service_fee' => 600,
            'total_amount' => 6600,
            'currency' => 'KES',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'customer_name' => $this->user->full_name,
            'customer_email' => $this->user->email,
            'customer_phone' => $this->user->phone ?? '',
        ]);

        // Create tickets using the service
        $ticketService = app(TicketService::class);
        $result = $ticketService->createTicketsForBooking($this->booking);
        $this->ticket = $result['tickets'][0];

        // Authenticate user
        Passport::actingAs($this->user, ['*']);
    }

    /**
     * Test user can view all their tickets
     */
    public function test_user_can_view_all_tickets()
    {
        $response = $this->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'tickets' => [
                        '*' => [
                            'id',
                            'ticket_code',
                            'ticket_type',
                            'holder_name',
                            'event' => [
                                'id',
                                'title',
                                'venue_name',
                                'event_date',
                            ],
                        ],
                    ],
                    'pagination',
                ],
            ]);

        $this->assertEquals(2, count($response->json('data.tickets')));
    }

    /**
     * Test user can view upcoming tickets
     */
    public function test_user_can_view_upcoming_tickets()
    {
        // Create a past event ticket
        $pastEvent = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'category_id' => $this->event->category_id,
            'event_date' => now()->subDays(10),
        ]);

        $pastBooking = Booking::create([
            'user_id' => $this->user->id,
            'event_id' => $pastEvent->id,
            'booking_reference' => 'BK'.strtoupper(\Str::random(8)),
            'ticket_quantity' => 1,
            'ticket_types' => [['name' => 'General', 'quantity' => 1, 'price' => 1000]],
            'subtotal' => 1000,
            'service_fee' => 100,
            'total_amount' => 1100,
            'currency' => 'KES',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'customer_name' => $this->user->full_name,
            'customer_email' => $this->user->email,
            'customer_phone' => '',
        ]);

        $ticketService = app(TicketService::class);
        $ticketService->createTicketsForBooking($pastBooking);

        $response = $this->getJson('/api/tickets/upcoming');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'tickets',
                    'grouped_by_date',
                    'pagination',
                ],
            ]);

        // Should only return the future event tickets (2 tickets)
        $this->assertEquals(2, count($response->json('data.tickets')));
    }

    /**
     * Test user can view past tickets
     */
    public function test_user_can_view_past_tickets()
    {
        // Update event to be in the past
        $this->event->update(['event_date' => now()->subDays(5)]);

        $response = $this->getJson('/api/tickets/past');

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data.tickets')));
    }

    /**
     * Test user can view specific ticket details
     */
    public function test_user_can_view_ticket_details()
    {
        $response = $this->getJson('/api/tickets/'.$this->ticket->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'ticket_code',
                    'ticket_type',
                    'holder_name',
                    'qr_data',
                    'status_info' => [
                        'is_valid',
                        'is_upcoming',
                        'is_transferable',
                        'days_until_event',
                    ],
                    'event',
                    'booking',
                ],
            ]);
    }

    /**
     * Test user cannot view other user's ticket
     */
    public function test_user_cannot_view_other_users_ticket()
    {
        Passport::actingAs($this->otherUser, ['*']);

        $response = $this->getJson('/api/tickets/'.$this->ticket->id);

        $response->assertStatus(404);
    }

    /**
     * Test user can view tickets by booking
     */
    public function test_user_can_view_tickets_by_booking()
    {
        $response = $this->getJson('/api/tickets/booking/'.$this->booking->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'booking',
                    'tickets',
                    'ticket_count',
                ],
            ]);

        $this->assertEquals(2, $response->json('data.ticket_count'));
    }

    /**
     * Test user can transfer transferable ticket
     */
    public function test_user_can_transfer_transferable_ticket()
    {
        // Get the General ticket (which is transferable)
        $generalTicket = Ticket::where('booking_id', $this->booking->id)
            ->where('ticket_type', 'General')
            ->first();

        $response = $this->postJson('/api/tickets/'.$generalTicket->id.'/transfer', [
            'recipient_email' => $this->otherUser->email,
            'reason' => 'Gift to friend',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Ticket transferred successfully',
            ]);

        // Verify ticket was transferred
        $generalTicket->refresh();
        $this->assertEquals($this->otherUser->id, $generalTicket->assigned_to);
        $this->assertEquals('transferred', $generalTicket->status);
    }

    /**
     * Test user cannot transfer non-transferable ticket
     */
    public function test_user_cannot_transfer_non_transferable_ticket()
    {
        // Get the VIP ticket (which is not transferable)
        $vipTicket = Ticket::where('booking_id', $this->booking->id)
            ->where('ticket_type', 'VIP')
            ->first();

        $response = $this->postJson('/api/tickets/'.$vipTicket->id.'/transfer', [
            'recipient_email' => $this->otherUser->email,
            'reason' => 'Gift to friend',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'This ticket type is not transferable',
            ]);
    }

    /**
     * Test user cannot transfer ticket to themselves
     */
    public function test_user_cannot_transfer_ticket_to_themselves()
    {
        $response = $this->postJson('/api/tickets/'.$this->ticket->id.'/transfer', [
            'recipient_email' => $this->user->email,
            'reason' => 'Test',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Cannot transfer ticket to yourself',
            ]);
    }

    /**
     * Test user can view transfer history
     */
    public function test_user_can_view_transfer_history()
    {
        // Transfer the ticket first
        $this->ticket->transferTo($this->otherUser->id, $this->user->id, 'Gift');

        $response = $this->getJson('/api/tickets/'.$this->ticket->id.'/transfer-history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'ticket_id',
                    'ticket_code',
                    'current_owner',
                    'transfer_history',
                    'transfer_count',
                ],
            ]);

        $this->assertEquals(1, $response->json('data.transfer_count'));
    }

    /**
     * Test user can download ticket data
     */
    public function test_user_can_download_ticket_data()
    {
        $response = $this->getJson('/api/tickets/'.$this->ticket->id.'/download');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'ticket_data',
                    'html',
                    'filename',
                    'qr_code',
                    'wallet_passes' => [
                        'apple',
                        'google',
                    ],
                ],
            ]);
    }

    /**
     * Test filtering tickets by event
     */
    public function test_can_filter_tickets_by_event()
    {
        // Create another event with tickets
        $anotherEvent = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'category_id' => $this->event->category_id,
        ]);

        $anotherBooking = Booking::create([
            'user_id' => $this->user->id,
            'event_id' => $anotherEvent->id,
            'booking_reference' => 'BK'.strtoupper(\Str::random(8)),
            'ticket_quantity' => 1,
            'ticket_types' => [['name' => 'General', 'quantity' => 1, 'price' => 1000]],
            'subtotal' => 1000,
            'service_fee' => 100,
            'total_amount' => 1100,
            'currency' => 'KES',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'customer_name' => $this->user->full_name,
            'customer_email' => $this->user->email,
            'customer_phone' => '',
        ]);

        $ticketService = app(TicketService::class);
        $ticketService->createTicketsForBooking($anotherBooking);

        // Filter by first event
        $response = $this->getJson('/api/tickets?event_id='.$this->event->id);

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data.tickets')));

        // Filter by second event
        $response = $this->getJson('/api/tickets?event_id='.$anotherEvent->id);

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data.tickets')));
    }

    /**
     * Test pagination of tickets
     */
    public function test_tickets_are_paginated()
    {
        $response = $this->getJson('/api/tickets?per_page=1');

        $response->assertStatus(200)
            ->assertJsonPath('data.pagination.per_page', 1)
            ->assertJsonPath('data.pagination.total', 2);

        $this->assertEquals(1, count($response->json('data.tickets')));
    }
}
