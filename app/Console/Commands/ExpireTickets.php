<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\Event;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:expire {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically expire tickets based on validity period and event end dates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }
        
        $this->info('Starting ticket expiration process...');
        
        // 1. Expire tickets past their valid_until date
        $expiredByValidity = $this->expireTicketsByValidity($isDryRun);
        
        // 2. Expire tickets from events that ended more than 24 hours ago
        $expiredByEvent = $this->expireTicketsByEventEnd($isDryRun);
        
        // 3. Log summary
        $totalExpired = $expiredByValidity + $expiredByEvent;
        
        $this->info("Process completed:");
        $this->info("- Tickets expired by validity period: {$expiredByValidity}");
        $this->info("- Tickets expired by event end: {$expiredByEvent}");
        $this->info("- Total tickets expired: {$totalExpired}");
        
        if ($totalExpired > 0) {
            Log::info('Ticket expiration completed', [
                'expired_by_validity' => $expiredByValidity,
                'expired_by_event' => $expiredByEvent,
                'total' => $totalExpired,
                'dry_run' => $isDryRun
            ]);
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Expire tickets that have passed their valid_until date
     */
    protected function expireTicketsByValidity(bool $isDryRun): int
    {
        $query = Ticket::where('status', 'valid')
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', now());
        
        $count = $query->count();
        
        if ($count > 0) {
            $this->info("Found {$count} tickets past their validity period");
            
            if (!$isDryRun) {
                $query->update([
                    'status' => 'expired',
                    'updated_at' => now()
                ]);
            }
        }
        
        return $count;
    }
    
    /**
     * Expire tickets from events that ended more than 24 hours ago
     */
    protected function expireTicketsByEventEnd(bool $isDryRun): int
    {
        // Get events that ended more than 24 hours ago
        $expiredEventIds = Event::where('listing_type', '!=', 'service')
            ->where(function ($q) {
                // Events with end_date that passed 24 hours ago
                $q->whereNotNull('end_date')
                    ->where('end_date', '<', now()->subHours(24));
            })
            ->orWhere(function ($q) {
                // Single-day events that passed 24 hours ago
                $q->whereNull('end_date')
                    ->whereNotNull('event_date')
                    ->where('event_date', '<', now()->subHours(24));
            })
            ->pluck('id');
        
        if ($expiredEventIds->isEmpty()) {
            return 0;
        }
        
        $query = Ticket::where('status', 'valid')
            ->whereIn('event_id', $expiredEventIds)
            ->whereNull('valid_until'); // Don't override tickets with specific validity
        
        $count = $query->count();
        
        if ($count > 0) {
            $this->info("Found {$count} tickets from events that ended over 24 hours ago");
            
            if (!$isDryRun) {
                $query->update([
                    'status' => 'expired',
                    'updated_at' => now()
                ]);
            }
        }
        
        return $count;
    }
}
