<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Organizer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Organizer $organizer;

    protected Event $event;

    protected EventCategory $category;

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
            'platform_fee' => 0, // Use organizer's commission
            'ticket_types' => [
                [
                    'name' => 'Regular',
                    'price' => 1000,
                    'quantity' => 50,
                    'max_per_order' => 10,
                ],
            ],
        ]);

        // Authenticate user
        Passport::actingAs($this->user, ['*']);
    }

    /**
     * Test that booking doesn't create transaction immediately
     */
    public function test_booking_does_not_create_transaction()
    {
        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Regular', 'quantity' => 2],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'booking',
                'booking_id',
                'booking_reference',
                'amount',
                'currency',
                'expires_at',
                'payment_options',
            ],
        ]);

        // Verify no transaction was created
        $this->assertEquals(0, Transaction::count());

        // Verify booking was created
        $booking = Booking::first();
        $this->assertNotNull($booking);
        $this->assertEquals('pending', $booking->status);
        $this->assertEquals('unpaid', $booking->payment_status);
        $this->assertNotNull($booking->expires_at);
    }

    /**
     * Test payment initialization creates transaction
     */
    public function test_payment_initialization_creates_transaction()
    {
        // First create booking
        $bookingResponse = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Regular', 'quantity' => 1],
            ],
        ]);

        $bookingId = $bookingResponse->json('data.booking_id');

        // Mock Paystack service
        $this->mock(\App\Services\PaystackService::class, function ($mock) {
            $mock->shouldReceive('generateReference')
                ->andReturn('NXI_TEST_REF');
            $mock->shouldReceive('initializeTransaction')
                ->andReturn([
                    'authorization_url' => 'https://checkout.paystack.com/test',
                    'access_code' => 'test_access_code',
                    'reference' => 'NXI_TEST_REF',
                ]);
        });

        // Initialize payment
        $response = $this->postJson('/api/payments/paystack/initialize', [
            'booking_id' => $bookingId,
            'payment_method' => 'card',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'transaction_id',
                'booking_id',
                'authorization_url',
                'reference',
                'amount',
                'currency',
            ],
        ]);

        // Verify transaction was created
        $this->assertEquals(1, Transaction::count());
        $transaction = Transaction::first();
        $this->assertEquals($bookingId, $transaction->booking_id);
        $this->assertEquals('pending', $transaction->status);

        // Verify booking was updated
        $booking = Booking::find($bookingId);
        $this->assertEquals('processing', $booking->payment_status);
    }

    /**
     * Test only valid payment methods are accepted
     */
    public function test_valid_payment_methods()
    {
        $response = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Regular', 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201);

        // Check available payment options
        $paymentOptions = $response->json('data.payment_options');
        $this->assertContains('card', $paymentOptions);
        $this->assertContains('mpesa', $paymentOptions);
        $this->assertCount(2, $paymentOptions); // Only card and mpesa
    }

    /**
     * Test expired booking cannot be paid
     */
    public function test_expired_booking_cannot_be_paid()
    {
        // Create an expired booking
        $booking = Booking::create([
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
            'expires_at' => now()->subMinutes(1), // Already expired
        ]);

        // Mock Paystack service
        $this->mock(\App\Services\PaystackService::class, function ($mock) {
            $mock->shouldNotReceive('initializeTransaction');
        });

        // Try to initialize payment
        $response = $this->postJson('/api/payments/paystack/initialize', [
            'booking_id' => $booking->id,
            'payment_method' => 'card',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
        $this->assertStringContainsString('expired', strtolower($response->json('message')));
    }

    /**
     * Test that payment can be re-initialized if first attempt failed
     */
    public function test_payment_can_be_reinitialized()
    {
        // Create booking
        $booking = Booking::create([
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
            'expires_at' => now()->addMinutes(30),
        ]);

        // Mock Paystack service
        $this->mock(\App\Services\PaystackService::class, function ($mock) {
            $mock->shouldReceive('generateReference')
                ->andReturn('NXI_TEST_REF');
            $mock->shouldReceive('initializeTransaction')
                ->twice()
                ->andReturn([
                    'authorization_url' => 'https://checkout.paystack.com/test',
                    'access_code' => 'test_access_code',
                    'reference' => 'NXI_TEST_REF',
                ]);
        });

        // First initialization
        $response1 = $this->postJson('/api/payments/paystack/initialize', [
            'booking_id' => $booking->id,
            'payment_method' => 'card',
        ]);
        $response1->assertStatus(200);

        // Second initialization (should reuse transaction)
        $response2 = $this->postJson('/api/payments/paystack/initialize', [
            'booking_id' => $booking->id,
            'payment_method' => 'card',
        ]);
        $response2->assertStatus(200);

        // Should only have one transaction
        $this->assertEquals(1, Transaction::count());
    }

    /**
     * Test M-Pesa payment initialization
     */
    public function test_mpesa_payment_initialization()
    {
        // Create booking
        $bookingResponse = $this->postJson('/api/bookings', [
            'event_id' => $this->event->id,
            'ticket_types' => [
                ['name' => 'Regular', 'quantity' => 1],
            ],
        ]);

        $bookingId = $bookingResponse->json('data.booking_id');

        // Mock Paystack service for M-Pesa
        $this->mock(\App\Services\PaystackService::class, function ($mock) {
            $mock->shouldReceive('generateReference')
                ->andReturn('NXI_MPESA_REF');
            $mock->shouldReceive('initializeTransaction')
                ->with(\Mockery::on(function ($args) {
                    // Verify mobile_money channel is included
                    return in_array('mobile_money', $args['channels'] ?? []);
                }))
                ->andReturn([
                    'authorization_url' => 'https://checkout.paystack.com/mpesa',
                    'access_code' => 'mpesa_access_code',
                    'reference' => 'NXI_MPESA_REF',
                ]);
        });

        // Initialize M-Pesa payment
        $response = $this->postJson('/api/payments/mpesa/initialize', [
            'booking_id' => $bookingId,
            'phone_number' => '254712345678',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify phone number was stored
        $booking = Booking::find($bookingId);
        $this->assertEquals('254712345678', $booking->customer_phone);
    }

    /**
     * Test booking cleanup command
     */
    public function test_expired_bookings_cleanup()
    {
        // Create multiple bookings with different states
        $activeBooking = Booking::create([
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
            'created_at' => now()->subMinutes(10), // Recent
        ]);

        $expiredBooking = Booking::create([
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

        // Force expired booking to be old
        DB::table('bookings')
            ->where('id', $expiredBooking->id)
            ->update(['created_at' => now()->subHours(1)]);

        $paidBooking = Booking::create([
            'user_id' => $this->user->id,
            'event_id' => $this->event->id,
            'booking_reference' => 'BK'.strtoupper(\Str::random(8)),
            'ticket_quantity' => 1,
            'ticket_types' => [['name' => 'Regular', 'quantity' => 1]],
            'subtotal' => 1000,
            'service_fee' => 100,
            'total_amount' => 1100,
            'currency' => 'KES',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'customer_phone' => '',
            'created_at' => now()->subHours(2), // Old but paid
        ]);

        // Run cleanup
        $this->artisan('bookings:cleanup-expired')
            ->expectsOutput('Cleaning up expired bookings...')
            ->expectsOutput('Successfully cancelled 1 expired booking(s).')
            ->assertExitCode(0);

        // Verify states
        $activeBooking->refresh();
        $this->assertEquals('pending', $activeBooking->status);

        $expiredBooking->refresh();
        $this->assertEquals('cancelled', $expiredBooking->status);

        $paidBooking->refresh();
        $this->assertEquals('confirmed', $paidBooking->status);
    }
}
