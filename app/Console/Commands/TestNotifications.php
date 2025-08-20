<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Booking;
use App\Models\Organizer;
use App\Notifications\Admin\NewOrganizerRegistration;
use App\Notifications\Organizer\NewBookingReceived;
use App\Notifications\User\BookingConfirmation;
use Illuminate\Console\Command;

class TestNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:test {type=all : Type of notification to test (admin|organizer|user|all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test notification system by sending sample notifications';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        
        $this->info('Testing notification system...');
        
        switch ($type) {
            case 'admin':
                $this->testAdminNotification();
                break;
            case 'organizer':
                $this->testOrganizerNotification();
                break;
            case 'user':
                $this->testUserNotification();
                break;
            case 'all':
                $this->testAdminNotification();
                $this->testOrganizerNotification();
                $this->testUserNotification();
                break;
            default:
                $this->error("Invalid type. Use: admin, organizer, user, or all");
                return 1;
        }
        
        $this->info('Notifications sent successfully! Check the database and email.');
        return 0;
    }
    
    protected function testAdminNotification(): void
    {
        $this->info('Sending admin notification...');
        
        // Get or create an admin user
        $admin = User::where('role', 'admin')->first();
        
        if (!$admin) {
            $this->error('No admin user found. Run: php artisan db:seed --class=AdminSeeder');
            return;
        }
        
        // Get or create an organizer
        $organizer = Organizer::first();
        
        if (!$organizer) {
            $this->warn('No organizer found. Creating a test organizer...');
            $organizer = Organizer::create([
                'id' => \Str::uuid(),
                'user_id' => User::where('role', 'organizer')->first()?->id ?? User::factory()->create(['role' => 'organizer'])->id,
                'business_name' => 'Test Events Company',
                'business_type' => 'company',
                'business_country' => 'KE',
                'business_timezone' => 'Africa/Nairobi',
                'default_currency' => 'KES',
                'commission_rate' => 10.00,
                'settlement_period_days' => 7,
                'is_active' => true,
                'is_verified' => false,
            ]);
        }
        
        // Send notification
        $admin->notify(new NewOrganizerRegistration($organizer));
        
        $this->info("✓ Admin notification sent to: {$admin->email}");
    }
    
    protected function testOrganizerNotification(): void
    {
        $this->info('Sending organizer notification...');
        
        // Get an organizer
        $organizer = Organizer::with('user')->first();
        
        if (!$organizer || !$organizer->user) {
            $this->error('No organizer with user found. Create an organizer first.');
            return;
        }
        
        // Create a test booking
        $booking = $this->createTestBooking($organizer);
        
        // Send notification
        $organizer->user->notify(new NewBookingReceived($booking));
        
        $this->info("✓ Organizer notification sent to: {$organizer->user->email}");
    }
    
    protected function testUserNotification(): void
    {
        $this->info('Sending user notification...');
        
        // Get or create a regular user
        $user = User::where('role', 'user')->first();
        
        if (!$user) {
            $this->warn('No regular user found. Creating a test user...');
            $user = User::factory()->create([
                'role' => 'user',
                'email' => 'testuser@example.com',
            ]);
        }
        
        // Create a test booking
        $organizer = Organizer::first();
        if (!$organizer) {
            $this->error('No organizer found. Create an organizer first.');
            return;
        }
        
        $booking = $this->createTestBooking($organizer, $user);
        
        // Send notification
        $user->notify(new BookingConfirmation($booking));
        
        $this->info("✓ User notification sent to: {$user->email}");
    }
    
    protected function createTestBooking($organizer, $user = null): Booking
    {
        // Get or create an event
        $event = \App\Models\Event::where('organizer_id', $organizer->id)->first();
        
        if (!$event) {
            $event = \App\Models\Event::create([
                'id' => \Str::uuid(),
                'organizer_id' => $organizer->id,
                'title' => 'Test Event for Notifications',
                'slug' => 'test-event-' . time(),
                'description' => 'This is a test event',
                'event_date' => now()->addDays(7),
                'event_end_date' => now()->addDays(7)->addHours(3),
                'venue_name' => 'Test Venue',
                'venue_address' => '123 Test Street',
                'city' => 'Nairobi',
                'country' => 'KE',
                'min_price' => 1000,
                'max_price' => 5000,
                'currency' => 'KES',
                'capacity' => 100,
                'status' => 'published',
                'category_id' => null,
                'is_featured' => false,
                'ticket_types' => [
                    ['name' => 'Regular', 'price' => 1000, 'quantity' => 50],
                    ['name' => 'VIP', 'price' => 5000, 'quantity' => 20],
                ],
            ]);
        }
        
        // Create a booking
        return Booking::create([
            'id' => \Str::uuid(),
            'booking_reference' => 'TEST-' . strtoupper(\Str::random(8)),
            'user_id' => $user?->id ?? User::where('role', 'user')->first()?->id,
            'event_id' => $event->id,
            'quantity' => 2,
            'total_amount' => 2000,
            'currency' => 'KES',
            'payment_status' => 'paid',
            'payment_method' => 'mpesa',
            'status' => 'confirmed',
            'customer_name' => $user?->full_name ?? 'Test Customer',
            'customer_email' => $user?->email ?? 'customer@test.com',
            'customer_phone' => $user?->phone_number ?? '+254712345678',
            'ticket_types' => [
                ['name' => 'Regular', 'price' => 1000, 'quantity' => 2],
            ],
        ]);
    }
}