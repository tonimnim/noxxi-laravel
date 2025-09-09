<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupPendingBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:cleanup-pending 
                            {--minutes=30 : Delete pending bookings older than this many minutes}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up pending bookings that were abandoned (payment not completed)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $minutes = $this->option('minutes');
        $dryRun = $this->option('dry-run');
        $cutoffTime = Carbon::now()->subMinutes($minutes);

        $this->info("Cleaning up pending bookings older than {$minutes} minutes...");
        $this->info("Cutoff time: {$cutoffTime}");

        // Find pending bookings older than cutoff time
        $query = Booking::where('status', 'pending')
            ->where('payment_status', '!=', 'paid')
            ->where('created_at', '<', $cutoffTime);

        $count = $query->count();

        if ($count === 0) {
            $this->info('No pending bookings to clean up.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} pending bookings to clean up.");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No bookings were actually deleted.');
            
            // Show sample of what would be deleted
            $samples = $query->limit(5)->get(['id', 'booking_reference', 'created_at', 'total_amount']);
            $this->table(
                ['ID', 'Reference', 'Created At', 'Amount'],
                $samples->map(fn($b) => [
                    substr($b->id, 0, 8) . '...',
                    $b->booking_reference,
                    $b->created_at->format('Y-m-d H:i'),
                    $b->total_amount
                ])
            );
        } else {
            DB::beginTransaction();
            try {
                // Delete the bookings
                $deletedCount = $query->delete();
                
                DB::commit();
                
                $this->info("✓ Successfully deleted {$deletedCount} pending bookings.");
                
                Log::info('Cleaned up pending bookings', [
                    'count' => $deletedCount,
                    'cutoff_time' => $cutoffTime,
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                
                $this->error('Failed to clean up bookings: ' . $e->getMessage());
                
                Log::error('Failed to clean up pending bookings', [
                    'error' => $e->getMessage(),
                ]);
                
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
