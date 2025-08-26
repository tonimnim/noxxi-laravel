<?php

namespace App\Console\Commands;

use App\Services\PaymentFlowService;
use Illuminate\Console\Command;

class CleanupExpiredBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:cleanup-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cancel expired bookings that were not paid within 30 minutes';

    protected PaymentFlowService $paymentFlowService;

    /**
     * Create a new command instance.
     */
    public function __construct(PaymentFlowService $paymentFlowService)
    {
        parent::__construct();
        $this->paymentFlowService = $paymentFlowService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Cleaning up expired bookings...');

        $cancelledCount = $this->paymentFlowService->cancelExpiredBookings();

        if ($cancelledCount > 0) {
            $this->info("Successfully cancelled {$cancelledCount} expired booking(s).");
        } else {
            $this->info('No expired bookings found.');
        }

        return Command::SUCCESS;
    }
}
