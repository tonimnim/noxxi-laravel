<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-event-reminders {--hours=24 : Hours before event to send reminder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send event reminder notifications to attendees';

    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        
        $this->info("Sending event reminders for events happening in {$hours} hours...");
        
        $count = $this->notificationService->sendEventReminders($hours);
        
        if ($count > 0) {
            $this->info("Successfully sent {$count} reminder notifications.");
        } else {
            $this->info("No reminders needed to be sent.");
        }
        
        return Command::SUCCESS;
    }
}