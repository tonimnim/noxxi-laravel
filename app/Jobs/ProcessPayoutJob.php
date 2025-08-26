<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Notifications\PayoutFailedNotification;
use App\Services\PaystackTransferService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPayoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    public $backoff = [60, 120, 300]; // Retry after 1min, 2min, 5min

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
    public function handle(PaystackTransferService $transferService): void
    {
        try {
            // Double-check payout status
            if (! in_array($this->payout->status, ['approved', 'processing'])) {
                Log::warning("Payout {$this->payout->id} is not in approved/processing status");

                return;
            }

            // Update status to processing
            $this->payout->update(['status' => 'processing']);

            // Initiate the transfer
            $result = $transferService->initiateTransfer($this->payout);

            if ($result['success']) {
                // Update payout with transfer details
                $this->payout->update([
                    'status' => 'processing',
                    'payment_reference' => $result['transfer_code'],
                    'transaction_reference' => $result['reference'],
                    'processed_at' => now(),
                ]);

                // Dispatch verification job to check status after 5 minutes
                VerifyPayoutStatusJob::dispatch($this->payout)
                    ->delay(now()->addMinutes(5));

                Log::info("Payout {$this->payout->id} successfully initiated with transfer code: {$result['transfer_code']}");

            } else {
                // Handle failure
                $this->handleFailure($result['message'] ?? 'Unknown error');
            }

        } catch (\Exception $e) {
            Log::error("Error processing payout {$this->payout->id}: ".$e->getMessage());
            $this->handleFailure($e->getMessage());

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle payout failure
     */
    private function handleFailure(string $reason): void
    {
        $this->payout->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);

        // Notify organizer
        if ($this->payout->organizer->user) {
            $this->payout->organizer->user->notify(
                new PayoutFailedNotification($this->payout, $reason)
            );
        }

        // Notify admin
        $this->notifyAdmin('Payout Failed',
            "Payout {$this->payout->reference} for {$this->payout->organizer->business_name} failed: {$reason}"
        );
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Payout job completely failed for {$this->payout->id}: ".$exception->getMessage());

        $this->payout->update([
            'status' => 'failed',
            'failure_reason' => 'Maximum retry attempts exceeded: '.$exception->getMessage(),
        ]);

        // Notify admin
        $this->notifyAdmin('Payout Job Failed',
            "Payout job for {$this->payout->reference} has failed after all retry attempts."
        );
    }

    /**
     * Notify admin via email
     */
    private function notifyAdmin(string $subject, string $message): void
    {
        try {
            // TODO: Implement admin notification
            Log::info("Admin notification: {$subject} - {$message}");
        } catch (\Exception $e) {
            Log::error('Failed to send admin notification: '.$e->getMessage());
        }
    }
}
