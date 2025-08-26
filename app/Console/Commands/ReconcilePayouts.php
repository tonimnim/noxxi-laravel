<?php

namespace App\Console\Commands;

use App\Jobs\VerifyPayoutStatusJob;
use App\Models\Payout;
use App\Models\User;
use App\Services\PaystackTransferService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class ReconcilePayouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payouts:reconcile {--days=7 : Number of days to look back}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile payout statuses with payment gateway and detect stuck payouts';

    protected PaystackTransferService $transferService;

    /**
     * Execute the console command.
     */
    public function handle(PaystackTransferService $transferService): int
    {
        $this->transferService = $transferService;
        
        $this->info('Starting payout reconciliation...');
        
        // Get payouts that need reconciliation
        $payouts = $this->getPayoutsForReconciliation();
        
        if ($payouts->isEmpty()) {
            $this->info('No payouts need reconciliation.');
            return Command::SUCCESS;
        }
        
        $this->info("Found {$payouts->count()} payouts to reconcile.");
        
        $stats = [
            'checked' => 0,
            'completed' => 0,
            'failed' => 0,
            'stuck' => 0,
            'expired' => 0,
        ];
        
        foreach ($payouts as $payout) {
            $this->line("Checking payout: {$payout->reference_number}");
            $stats['checked']++;
            
            try {
                $result = $this->reconcilePayout($payout);
                
                switch ($result) {
                    case 'completed':
                        $stats['completed']++;
                        $this->info("  ✓ Marked as completed");
                        break;
                    case 'failed':
                        $stats['failed']++;
                        $this->error("  ✗ Marked as failed");
                        break;
                    case 'stuck':
                        $stats['stuck']++;
                        $this->warn("  ⚠ Detected as stuck");
                        break;
                    case 'expired':
                        $stats['expired']++;
                        $this->warn("  ⚠ Marked as expired");
                        break;
                }
                
            } catch (\Exception $e) {
                $this->error("  Error reconciling payout: {$e->getMessage()}");
                Log::error('Payout reconciliation error', [
                    'payout_id' => $payout->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Send summary to admins
        $this->sendReconciliationReport($stats);
        
        $this->info('Reconciliation complete!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Checked', $stats['checked']],
                ['Completed', $stats['completed']],
                ['Failed', $stats['failed']],
                ['Stuck', $stats['stuck']],
                ['Expired', $stats['expired']],
            ]
        );
        
        return Command::SUCCESS;
    }
    
    /**
     * Get payouts that need reconciliation
     */
    protected function getPayoutsForReconciliation()
    {
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);
        
        return Payout::whereIn('status', ['approved', 'processing'])
            ->where('created_at', '>=', $cutoffDate)
            ->orderBy('created_at', 'asc')
            ->get();
    }
    
    /**
     * Reconcile a single payout
     */
    protected function reconcilePayout(Payout $payout): string
    {
        // Check if payout has been stuck in approved state for too long
        if ($payout->status === 'approved' && $payout->approved_at) {
            $hoursSinceApproval = $payout->approved_at->diffInHours(now());
            
            // If approved more than 24 hours ago but not processed, mark as stuck
            if ($hoursSinceApproval > 24) {
                $this->handleStuckPayout($payout);
                return 'stuck';
            }
        }
        
        // Check if payout has been processing for too long
        if ($payout->status === 'processing' && $payout->processed_at) {
            $hoursSinceProcessing = $payout->processed_at->diffInHours(now());
            
            // If processing for more than 48 hours, check with gateway
            if ($hoursSinceProcessing > 48) {
                // Check with Paystack if we have a reference
                if ($payout->transaction_reference) {
                    $result = $this->transferService->verifyTransfer($payout->transaction_reference);
                    
                    if ($result['success']) {
                        switch ($result['status']) {
                            case 'success':
                                $payout->update([
                                    'status' => 'completed',
                                    'completed_at' => now(),
                                ]);
                                return 'completed';
                                
                            case 'failed':
                            case 'reversed':
                                $payout->update([
                                    'status' => 'failed',
                                    'failure_reason' => $result['message'] ?? 'Transfer failed at gateway',
                                ]);
                                return 'failed';
                                
                            default:
                                // Still processing - mark as stuck if too long
                                if ($hoursSinceProcessing > 72) {
                                    $this->handleStuckPayout($payout);
                                    return 'stuck';
                                }
                        }
                    }
                } else {
                    // No transaction reference after 48 hours of processing - stuck
                    $this->handleStuckPayout($payout);
                    return 'stuck';
                }
            }
        }
        
        // Check for expired pending payouts (never approved after 30 days)
        if ($payout->status === 'pending') {
            $daysSincePending = $payout->created_at->diffInDays(now());
            
            if ($daysSincePending > 30) {
                $payout->update([
                    'status' => 'expired',
                    'admin_notes' => ($payout->admin_notes ?? '') . "\nAuto-expired after 30 days - " . now()->toDateTimeString(),
                ]);
                return 'expired';
            }
        }
        
        return 'unchanged';
    }
    
    /**
     * Handle stuck payout
     */
    protected function handleStuckPayout(Payout $payout): void
    {
        // Update status
        $payout->update([
            'admin_notes' => ($payout->admin_notes ?? '') . "\nDetected as stuck at " . now()->toDateTimeString(),
        ]);
        
        // Alert admins
        $admins = User::where('role', 'admin')->get();
        
        Notification::send($admins, new \Illuminate\Notifications\SimpleNotification(
            'Stuck Payout Detected',
            "Payout {$payout->reference_number} appears to be stuck and needs manual intervention.",
            'warning'
        ));
        
        // Log for monitoring
        Log::warning('Stuck payout detected', [
            'payout_id' => $payout->id,
            'reference' => $payout->reference_number,
            'status' => $payout->status,
            'organizer' => $payout->organizer->business_name,
        ]);
    }
    
    /**
     * Send reconciliation report to admins
     */
    protected function sendReconciliationReport(array $stats): void
    {
        try {
            $admins = User::where('role', 'admin')->get();
            
            if ($stats['stuck'] > 0 || $stats['failed'] > 0) {
                // Send alert if there are issues
                foreach ($admins as $admin) {
                    Mail::raw(
                        "Payout Reconciliation Report:\n\n" .
                        "Checked: {$stats['checked']}\n" .
                        "Completed: {$stats['completed']}\n" .
                        "Failed: {$stats['failed']}\n" .
                        "Stuck: {$stats['stuck']}\n" .
                        "Expired: {$stats['expired']}\n\n" .
                        "Please review stuck and failed payouts in the admin panel.",
                        function ($message) use ($admin) {
                            $message->to($admin->email)
                                ->subject('Payout Reconciliation Alert - Issues Detected');
                        }
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send reconciliation report: ' . $e->getMessage());
        }
    }
}