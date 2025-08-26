<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Notifications\PayoutCompletedNotification;
use App\Services\AvailableBalanceService;
use App\Services\PaystackTransferService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyPayoutStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $timeout = 60;

    protected Payout $payout;

    /**
     * Create a new job instance.
     */
    public function __construct(Payout $payout)
    {
        $this->payout = $payout;
        $this->queue = 'payouts';
    }

    /**
     * Execute the job.
     */
    public function handle(PaystackTransferService $transferService, AvailableBalanceService $balanceService): void
    {
        try {
            // Only verify if payout is still processing
            if ($this->payout->status !== 'processing') {
                Log::info("Payout {$this->payout->id} is no longer in processing status");

                return;
            }

            // Verify transfer status
            $result = $transferService->verifyTransfer($this->payout->transaction_reference);

            if (! $result['success']) {
                Log::warning("Failed to verify payout {$this->payout->id}: ".($result['message'] ?? 'Unknown error'));

                // Retry verification after 10 minutes
                self::dispatch($this->payout)->delay(now()->addMinutes(10));

                return;
            }

            $status = $result['status'];

            switch ($status) {
                case 'success':
                    $this->handleSuccess($result['data']);
                    break;

                case 'failed':
                case 'reversed':
                    $this->handleFailure($result['data']);
                    break;

                case 'pending':
                case 'processing':
                    // Still processing, check again later
                    self::dispatch($this->payout)->delay(now()->addMinutes(10));
                    break;

                default:
                    Log::warning("Unknown payout status '{$status}' for payout {$this->payout->id}");
                    self::dispatch($this->payout)->delay(now()->addMinutes(30));
            }

        } catch (\Exception $e) {
            Log::error("Error verifying payout {$this->payout->id}: ".$e->getMessage());

            // Retry verification
            if ($this->attempts() < $this->tries) {
                $this->release(300); // Retry after 5 minutes
            }
        }
    }

    /**
     * Handle successful payout
     */
    private function handleSuccess(array $data): void
    {
        $this->payout->update([
            'status' => 'completed',
            'completed_at' => now(),
            'paid_at' => now(),
            'bank_reference' => $data['reference'] ?? null,
        ]);

        // Update organizer's last payout date and total commission paid
        $organizer = $this->payout->organizer;
        $organizer->update([
            'last_payout_at' => now(),
            'total_commission_paid' => $organizer->total_commission_paid + $this->payout->commission_amount,
        ]);

        // Clear balance cache
        app(AvailableBalanceService::class)->clearCache($organizer);

        // Send notification to organizer
        if ($organizer->user) {
            $organizer->user->notify(new PayoutCompletedNotification($this->payout));
        }

        Log::info("Payout {$this->payout->id} completed successfully");
    }

    /**
     * Handle failed payout
     */
    private function handleFailure(array $data): void
    {
        $reason = $data['gateway_response'] ?? $data['message'] ?? 'Transfer failed';

        $this->payout->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);

        // Clear balance cache so the amount becomes available again
        app(AvailableBalanceService::class)->clearCache($this->payout->organizer);

        // Notify organizer
        if ($this->payout->organizer->user) {
            $this->payout->organizer->user->notify(
                new \App\Notifications\PayoutFailedNotification($this->payout, $reason)
            );
        }

        Log::error("Payout {$this->payout->id} failed: {$reason}");
    }
}
