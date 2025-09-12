<?php

namespace App\Jobs;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessTicketCheckIn implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [10, 30, 60]; // Exponential backoff

    /**
     * The maximum number of unhandled exceptions.
     */
    public $maxExceptions = 2;

    /**
     * Timeout for the job (seconds)
     */
    public $timeout = 30;

    protected array $checkInData;

    protected bool $isBatch;

    /**
     * Create a new job instance.
     */
    public function __construct(array $checkInData, bool $isBatch = false)
    {
        // Ensure all IDs are strings, not UUID objects
        if (isset($checkInData['ticket_id'])) {
            $checkInData['ticket_id'] = (string) $checkInData['ticket_id'];
        }
        if (isset($checkInData['event_id'])) {
            $checkInData['event_id'] = (string) $checkInData['event_id'];
        }
        if (isset($checkInData['user_id'])) {
            $checkInData['user_id'] = (string) $checkInData['user_id'];
        }

        $this->checkInData = $checkInData;
        $this->isBatch = $isBatch;
        $this->onQueue('check-ins'); // Dedicated queue for check-ins
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->isBatch) {
            $this->processBatchCheckIn();
        } else {
            $this->processSingleCheckIn($this->checkInData);
        }
    }

    /**
     * Process a single check-in with optimistic locking
     */
    protected function processSingleCheckIn(array $data): bool
    {
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                return DB::transaction(function () use ($data) {
                    // Ensure ticket_id is a string
                    $ticketId = is_object($data['ticket_id']) ? (string) $data['ticket_id'] : $data['ticket_id'];

                    // Ensure user_id is also a string
                    $userId = is_object($data['user_id']) ? (string) $data['user_id'] : $data['user_id'];

                    Log::info('Processing check-in', [
                        'ticket_id' => $ticketId,
                        'user_id' => $userId,
                    ]);

                    // Lock and get the ticket with version
                    $ticket = Ticket::where('id', $ticketId)
                        ->lockForUpdate()
                        ->first();

                    if (! $ticket) {
                        Log::warning('Check-in failed: Ticket not found', ['ticket_id' => $ticketId]);

                        return false;
                    }

                    // Check if already used (idempotent check)
                    if ($ticket->status === 'used') {
                        // If same user, it's a duplicate scan - that's OK
                        if ($ticket->used_by === $userId) {
                            return true; // Idempotent success
                        }

                        // Different user - conflict
                        Log::warning('Check-in conflict: Ticket already used', [
                            'ticket_id' => $ticketId,
                            'original_user' => $ticket->used_by,
                            'new_user' => $userId,
                            'used_at' => $ticket->used_at,
                        ]);

                        return false;
                    }

                    // Perform the check-in with version increment
                    $updated = Ticket::where('id', $ticket->id)
                        ->where('version', $ticket->version) // Optimistic lock check
                        ->update([
                            'status' => 'used',
                            'used_at' => $data['scanned_at'] ?? now(),
                            'used_by' => $userId,
                            'device_fingerprint' => $data['device_id'] ?? null,
                            'version' => $ticket->version + 1,
                        ]);

                    if (! $updated) {
                        // Version mismatch - someone else updated the ticket
                        throw new \Exception('Optimistic lock failed');
                    }

                    // Cache the check-in for quick duplicate detection
                    $cacheKey = "ticket_checked_{$ticket->id}";
                    Cache::put($cacheKey, [
                        'user_id' => $userId,
                        'checked_at' => now()->toIso8601String(),
                    ], 3600); // Cache for 1 hour

                    // Update stats cache
                    $this->updateEventStats($ticket->event_id);

                    Log::info('Ticket checked in successfully', [
                        'ticket_id' => $ticket->id,
                        'code' => $ticket->ticket_code,
                    ]);

                    return true;
                });
            } catch (\Exception $e) {
                $attempt++;
                if ($attempt >= $maxRetries) {
                    Log::error('Check-in failed after retries', [
                        'ticket_id' => $data['ticket_id'],
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }

                // Wait before retry (with jitter)
                usleep(random_int(100000, 500000)); // 100-500ms
            }
        }

        return false;
    }

    /**
     * Process batch check-ins
     */
    protected function processBatchCheckIn(): void
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($this->checkInData as $checkIn) {
            try {
                $success = $this->processSingleCheckIn($checkIn);
                $results[$checkIn['ticket_id']] = [
                    'success' => $success,
                    'message' => $success ? 'Checked in' : 'Failed',
                ];

                if ($success) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $results[$checkIn['ticket_id']] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
                $failureCount++;
            }
        }

        Log::info('Batch check-in completed', [
            'total' => count($this->checkInData),
            'success' => $successCount,
            'failed' => $failureCount,
        ]);
    }

    /**
     * Update cached event statistics
     */
    protected function updateEventStats(string $eventId): void
    {
        $cacheKey = "event_stats_{$eventId}";
        Cache::forget($cacheKey); // Clear cache to force recalculation
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Check-in job failed', [
            'data' => $this->checkInData,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Store failed check-in for manual review if needed
        DB::table('pending_check_ins')->insert([
            'id' => \Str::uuid(),
            'ticket_id' => $this->checkInData['ticket_id'] ?? null,
            'event_id' => $this->checkInData['event_id'] ?? null,
            'checked_by' => $this->checkInData['user_id'] ?? null,
            'device_id' => $this->checkInData['device_id'] ?? null,
            'scanned_at' => $this->checkInData['scanned_at'] ?? now(),
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
