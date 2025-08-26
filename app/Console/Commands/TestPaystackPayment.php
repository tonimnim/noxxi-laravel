<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestPaystackPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paystack:test {--email=test@example.com} {--amount=1000}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Paystack payment integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Paystack Payment Test...');

        // Check if Paystack keys are configured
        if (! config('services.paystack.secret_key') || ! config('services.paystack.public_key')) {
            $this->error('❌ Paystack keys not configured in .env file');
            $this->info('Please add:');
            $this->info('PAYSTACK_PUBLIC_KEY=pk_test_...');
            $this->info('PAYSTACK_SECRET_KEY=sk_test_...');

            return 1;
        }

        $this->info('✅ Paystack keys found');

        // Get or create test user
        $email = $this->option('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->info("Creating test user: $email");
            $user = User::create([
                'id' => Str::uuid(),
                'full_name' => 'Test User',
                'email' => $email,
                'password' => bcrypt('password'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]);
        }

        $this->info("✅ Using user: {$user->email}");

        // Get first available event
        $event = Event::where('status', 'published')
            ->where('event_date', '>', now())
            ->first();

        if (! $event) {
            $this->error('❌ No published upcoming events found');
            $this->info('Please create an event first through the organizer dashboard');

            return 1;
        }

        $this->info("✅ Using event: {$event->title}");

        // Create a test booking
        $amount = $this->option('amount');
        $booking = Booking::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'event_id' => $event->id,
            'booking_reference' => 'TEST_'.strtoupper(Str::random(10)),
            'quantity' => 1,
            'subtotal' => $amount,
            'service_fee' => $amount * 0.03, // 3% service fee
            'total_amount' => $amount * 1.03,
            'currency' => $event->currency ?? 'NGN',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'card',
            'customer_name' => $user->full_name,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone_number ?? '+234123456789',
            'ticket_types' => [
                [
                    'name' => 'General',
                    'price' => $amount,
                    'quantity' => 1,
                ],
            ],
        ]);

        $this->info("✅ Created booking: {$booking->booking_reference}");

        // Create transaction
        $transaction = Transaction::create([
            'id' => Str::uuid(),
            'type' => Transaction::TYPE_TICKET_SALE,
            'booking_id' => $booking->id,
            'organizer_id' => $event->organizer_id,
            'user_id' => $user->id,
            'amount' => $booking->total_amount,
            'currency' => $booking->currency,
            'commission_amount' => $booking->total_amount * 0.10,
            'gateway_fee' => $booking->total_amount * 0.015,
            'net_amount' => $booking->total_amount * 0.885,
            'payment_gateway' => 'paystack',
            'payment_method' => 'card',
            'status' => Transaction::STATUS_PENDING,
        ]);

        $this->info("✅ Created transaction: {$transaction->id}");

        // Initialize Paystack payment
        try {
            $paystackService = app(PaystackService::class);

            $reference = $paystackService->generateReference('TEST');
            $transaction->payment_reference = $reference;
            $transaction->save();

            $result = $paystackService->initializeTransaction([
                'email' => $user->email,
                'amount' => $transaction->amount,
                'reference' => $reference,
                'currency' => $transaction->currency,
                'callback_url' => config('services.paystack.callback_url'),
                'metadata' => [
                    'transaction_id' => $transaction->id,
                    'booking_id' => $booking->id,
                    'user_id' => $user->id,
                    'event_title' => $event->title,
                ],
            ]);

            $this->info('✅ Payment initialized successfully!');
            $this->newLine();

            $this->info('📋 Payment Details:');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Amount', $transaction->currency.' '.number_format($transaction->amount, 2)],
                    ['Reference', $reference],
                    ['Transaction ID', $transaction->id],
                    ['Booking ID', $booking->id],
                ]
            );

            $this->newLine();
            $this->info('🌐 Payment URL:');
            $this->line($result['authorization_url']);

            $this->newLine();
            $this->info('📝 Test Card Details:');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Card Number', '4084084084084081'],
                    ['CVV', '408'],
                    ['Expiry', 'Any future date (e.g., 12/25)'],
                    ['PIN', '0000'],
                ]
            );

            $this->newLine();
            $this->info('🔄 Webhook URL (update in Paystack dashboard):');
            $webhookUrl = config('services.paystack.webhook_url');
            $this->line($webhookUrl);

            if (str_contains($webhookUrl, 'ngrok')) {
                $this->info('✅ ngrok URL detected - webhooks should work!');
            } else {
                $this->warn('⚠️  Local URL detected - webhooks won\'t work unless you use ngrok');
                $this->info('Run: ngrok http 8000');
                $this->info('Then update PAYSTACK_WEBHOOK_URL in .env with the ngrok URL');
            }

            $this->newLine();
            $this->info('👉 Next steps:');
            $this->info('1. Click the payment URL above');
            $this->info('2. Complete payment with test card');
            $this->info('3. Check the webhook was received');
            $this->info('4. Verify payment status:');
            $this->line("   php artisan paystack:verify {$transaction->id}");

        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
