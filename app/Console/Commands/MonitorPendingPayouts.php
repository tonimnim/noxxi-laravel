<?php

namespace App\Console\Commands;

use App\Models\Payout;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MonitorPendingPayouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payouts:monitor-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor pending payouts and alert admins of those requiring approval';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking pending payouts...');
        
        // Get pending payouts grouped by age
        $pendingPayouts = Payout::where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();
            
        if ($pendingPayouts->isEmpty()) {
            $this->info('No pending payouts found.');
            return Command::SUCCESS;
        }
        
        // Categorize by urgency
        $urgent = [];  // Over 48 hours old
        $normal = [];  // 24-48 hours old
        $recent = [];  // Less than 24 hours old
        
        foreach ($pendingPayouts as $payout) {
            $hoursOld = $payout->created_at->diffInHours(now());
            
            if ($hoursOld > 48) {
                $urgent[] = $payout;
            } elseif ($hoursOld > 24) {
                $normal[] = $payout;
            } else {
                $recent[] = $payout;
            }
        }
        
        // Display summary
        $this->table(
            ['Priority', 'Count', 'Age Range'],
            [
                ['🔴 Urgent', count($urgent), 'Over 48 hours'],
                ['🟡 Normal', count($normal), '24-48 hours'],
                ['🟢 Recent', count($recent), 'Under 24 hours'],
            ]
        );
        
        // Send alert if there are urgent payouts
        if (count($urgent) > 0) {
            $this->warn('⚠️  ' . count($urgent) . ' urgent payouts need immediate attention!');
            $this->sendUrgentAlert($urgent);
        }
        
        // Show details of urgent payouts
        if (count($urgent) > 0) {
            $this->info("\nUrgent Payouts Details:");
            foreach ($urgent as $payout) {
                $this->line(sprintf(
                    "  - %s | %s %s | %s | %d hours old",
                    $payout->reference_number,
                    $payout->currency,
                    number_format($payout->net_amount, 2),
                    $payout->organizer->business_name,
                    $payout->created_at->diffInHours(now())
                ));
            }
        }
        
        // Log statistics
        Log::info('Pending payouts monitored', [
            'urgent' => count($urgent),
            'normal' => count($normal),
            'recent' => count($recent),
            'total' => $pendingPayouts->count(),
        ]);
        
        return Command::SUCCESS;
    }
    
    /**
     * Send urgent alert to admins
     */
    protected function sendUrgentAlert(array $urgentPayouts): void
    {
        try {
            $admins = User::where('role', 'admin')->get();
            
            $message = "URGENT: " . count($urgentPayouts) . " payouts have been pending for over 48 hours!\n\n";
            $message .= "Details:\n";
            $totalAmount = 0;
            
            foreach ($urgentPayouts as $payout) {
                $totalAmount += $payout->net_amount;
                $message .= sprintf(
                    "- %s: %s %s from %s (pending %d hours)\n",
                    $payout->reference_number,
                    $payout->currency,
                    number_format($payout->net_amount, 2),
                    $payout->organizer->business_name,
                    $payout->created_at->diffInHours(now())
                );
            }
            
            $message .= "\nTotal Amount Pending: KES " . number_format($totalAmount, 2);
            $message .= "\n\nPlease log in to the admin panel to review and approve these payouts.";
            
            foreach ($admins as $admin) {
                Mail::raw($message, function ($mail) use ($admin, $urgentPayouts) {
                    $mail->to($admin->email)
                        ->subject('🔴 URGENT: ' . count($urgentPayouts) . ' Payouts Need Approval');
                });
            }
            
            $this->info('Alert sent to ' . $admins->count() . ' admin(s).');
            
        } catch (\Exception $e) {
            Log::error('Failed to send urgent payout alert: ' . $e->getMessage());
            $this->error('Failed to send admin alert: ' . $e->getMessage());
        }
    }
}