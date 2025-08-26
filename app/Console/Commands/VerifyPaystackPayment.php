<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\PaystackService;
use Illuminate\Console\Command;

class VerifyPaystackPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paystack:verify {transaction_id : The transaction ID to verify}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify a Paystack payment transaction';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('transaction_id');

        $this->info('🔍 Verifying payment...');

        // Find the transaction
        $transaction = Transaction::with(['booking', 'booking.event', 'booking.tickets'])
            ->find($transactionId);

        if (! $transaction) {
            $this->error('❌ Transaction not found');

            return 1;
        }

        $this->info("✅ Found transaction: {$transaction->payment_reference}");

        // Display current status
        $this->table(
            ['Field', 'Value'],
            [
                ['Transaction ID', $transaction->id],
                ['Payment Reference', $transaction->payment_reference],
                ['Amount', $transaction->currency.' '.number_format($transaction->amount, 2)],
                ['Status', $transaction->status],
                ['Payment Gateway', $transaction->payment_gateway],
                ['Created', $transaction->created_at->format('Y-m-d H:i:s')],
            ]
        );

        // If transaction has a Paystack reference, verify with API
        if ($transaction->payment_reference && $transaction->payment_gateway === 'paystack') {
            $this->info('🔄 Checking with Paystack API...');

            try {
                $paystackService = app(PaystackService::class);
                $verification = $paystackService->verifyTransaction($transaction->payment_reference);

                if ($verification['success']) {
                    $this->newLine();
                    $this->info('📊 Paystack Verification Result:');
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Status', $verification['status']],
                            ['Amount', $verification['currency'].' '.number_format($verification['amount'], 2)],
                            ['Channel', $verification['channel'] ?? 'N/A'],
                            ['Paid At', $verification['paid_at'] ?? 'N/A'],
                            ['Customer Email', $verification['customer']['email'] ?? 'N/A'],
                        ]
                    );

                    if ($verification['status'] === 'success') {
                        $this->info('✅ Payment verified successfully!');

                        // Check if tickets were created
                        $ticketCount = $transaction->booking->tickets()->count();
                        if ($ticketCount > 0) {
                            $this->info("🎫 {$ticketCount} ticket(s) created for this booking");

                            // Show ticket details
                            $tickets = $transaction->booking->tickets;
                            $ticketData = [];
                            foreach ($tickets as $ticket) {
                                $ticketData[] = [
                                    substr($ticket->id, 0, 8).'...',
                                    $ticket->ticket_number,
                                    $ticket->ticket_type,
                                    $ticket->status,
                                    $ticket->checked_in ? 'Yes' : 'No',
                                ];
                            }

                            $this->table(
                                ['Ticket ID', 'Number', 'Type', 'Status', 'Checked In'],
                                $ticketData
                            );
                        } else {
                            $this->warn('⚠️  No tickets created yet for this booking');
                        }
                    } else {
                        $this->warn('⚠️  Payment not yet successful. Status: '.$verification['status']);
                    }
                } else {
                    $this->error('❌ Failed to verify with Paystack: '.($verification['message'] ?? 'Unknown error'));
                }

            } catch (\Exception $e) {
                $this->error('❌ Error verifying payment: '.$e->getMessage());
            }
        } else {
            $this->info('ℹ️  No Paystack reference available for verification');
        }

        // Check webhook logs
        $this->newLine();
        $this->info('📝 Recent webhook activity:');
        $webhookFile = storage_path('logs/laravel.log');
        if (file_exists($webhookFile)) {
            $logs = shell_exec("grep -i 'paystack' ".escapeshellarg($webhookFile).' | tail -5');
            if ($logs) {
                $this->line($logs);
            } else {
                $this->line('No recent Paystack webhook activity found in logs');
            }
        }

        return 0;
    }
}
