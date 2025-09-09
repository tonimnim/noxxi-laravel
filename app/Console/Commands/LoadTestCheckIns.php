<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTicketCheckIn;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class LoadTestCheckIns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:check-ins 
                            {requests=1000 : Number of check-in requests to simulate}
                            {--scanners=10 : Number of concurrent scanners}
                            {--events=5 : Number of different events}
                            {--batch : Use batch check-ins}
                            {--realtime : Test with real-time processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Load test the check-in system with simulated concurrent scanners';

    protected $startTime;
    protected $checkInsProcessed = 0;
    protected $errors = 0;
    protected $duplicates = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $totalRequests = (int) $this->argument('requests');
        $numScanners = (int) $this->option('scanners');
        $numEvents = (int) $this->option('events');
        $useBatch = $this->option('batch');
        $realtime = $this->option('realtime');

        $this->info("🚀 Starting Load Test");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 Configuration:");
        $this->info("  • Total Requests: " . number_format($totalRequests));
        $this->info("  • Concurrent Scanners: $numScanners");
        $this->info("  • Events: $numEvents");
        $this->info("  • Mode: " . ($useBatch ? 'Batch' : 'Individual'));
        $this->info("  • Processing: " . ($realtime ? 'Real-time' : 'Queued'));
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        // Setup test data
        $this->info("\n📝 Setting up test data...");
        $testData = $this->setupTestData($totalRequests, $numEvents, $numScanners);
        
        // Clear queue and stats
        if (!$realtime) {
            $this->info("🧹 Clearing queue...");
            Queue::connection()->flushDb();
        }
        
        // Start timing
        $this->startTime = microtime(true);
        $this->info("\n⏱️  Starting test at " . now()->format('H:i:s.u'));
        
        if ($useBatch) {
            $this->runBatchTest($testData, $numScanners);
        } else {
            $this->runIndividualTest($testData, $realtime);
        }
        
        // Calculate results
        $duration = microtime(true) - $this->startTime;
        $this->displayResults($totalRequests, $duration);
        
        // Cleanup
        if ($this->confirm('Clean up test data?', true)) {
            $this->cleanupTestData($testData);
        }
    }

    protected function setupTestData($totalRequests, $numEvents, $numScanners): array
    {
        $bar = $this->output->createProgressBar($totalRequests);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        
        // Get a category for test events
        $category = DB::table('event_categories')->first();
        if (!$category) {
            $this->error('No event categories found. Please run seeders first.');
            return [];
        }
        
        // Get or create a test organizer
        $organizer = DB::table('organizers')->first();
        if (!$organizer) {
            // Create a test user and organizer
            $testUser = User::firstOrCreate(
                ['email' => 'loadtest@example.com'],
                [
                    'full_name' => 'Load Test Organizer',
                    'phone' => '+254700000000',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );
            
            DB::table('organizers')->insert([
                'id' => \Str::uuid(),
                'user_id' => $testUser->id,
                'business_name' => 'Load Test Events',
                'business_type' => 'individual',
                'status' => 'active',
                'commission_rate' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $organizer = DB::table('organizers')->where('user_id', $testUser->id)->first();
        }
        
        // Create test events
        $events = [];
        $bar->setMessage('Creating events...');
        for ($i = 0; $i < $numEvents; $i++) {
            $events[] = Event::create([
                'title' => "Load Test Event $i",
                'organizer_id' => $organizer->id,
                'category_id' => $category->id,
                'event_date' => now()->addDays(7),
                'venue_name' => 'Test Venue',
                'venue_address' => 'Test Address',
                'city' => 'Nairobi',
                'capacity' => 10000,  // Add capacity
                'min_price' => 1000,
                'max_price' => 5000,
                'currency' => 'KES',
                'status' => 'published',
                'check_in_enabled' => true,
                'ticket_types' => json_encode([]),
                'description' => 'Load test event for performance testing',
            ]);
        }
        
        // Create scanner users first
        $scanners = [];
        for ($i = 0; $i < $numScanners; $i++) {
            $scanners[] = User::firstOrCreate(
                ['email' => "scanner$i@test.com"],
                [
                    'full_name' => "Scanner $i",
                    'phone_number' => "+254700" . str_pad($i, 6, '0', STR_PAD_LEFT),
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );
        }
        
        // Create test tickets
        $tickets = [];
        $bar->setMessage('Creating tickets...');
        
        for ($i = 0; $i < $totalRequests; $i++) {
            $event = $events[array_rand($events)];
            $ticketCode = 'TEST' . str_pad($i, 6, '0', STR_PAD_LEFT);
            $ticketId = \Str::uuid();
            
            // Create a fake booking first (simpler approach)
            if ($i % 100 == 0) {
                // Create bookings in batches of 100 tickets
                $bookingId = \Str::uuid();
                DB::table('bookings')->insert([
                    'id' => $bookingId,
                    'user_id' => $scanners[0]->id,
                    'event_id' => $event->id,
                    'booking_reference' => 'TEST-' . \Str::random(8),
                    'quantity' => 100,
                    'total_amount' => 100000,
                    'currency' => 'KES',
                    'status' => 'confirmed',
                    'payment_method' => 'test',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            $tickets[] = [
                'id' => $ticketId,
                'event_id' => $event->id,
                'booking_id' => $bookingId ?? \Str::uuid(),
                'ticket_code' => $ticketCode,
                'ticket_hash' => hash('sha256', $ticketCode . $event->id),
                'ticket_type' => 'Regular',
                'price' => 1000,
                'currency' => 'KES',
                'status' => 'valid',
                'holder_name' => "Test User $i",
                'holder_email' => "test$i@example.com",
                'created_at' => now(),
                'updated_at' => now(),
                'version' => 1,
            ];
            
            if (count($tickets) >= 1000) {
                DB::table('tickets')->insert($tickets);
                $tickets = [];
            }
            
            $bar->advance();
        }
        
        if (!empty($tickets)) {
            DB::table('tickets')->insert($tickets);
        }
        
        $bar->finish();
        $this->newLine();
        
        return [
            'events' => $events,
            'ticket_ids' => DB::table('tickets')
                ->whereIn('event_id', collect($events)->pluck('id'))
                ->pluck('id')
                ->toArray(),
            'scanners' => $scanners,
        ];
    }

    protected function runIndividualTest($testData, $realtime): void
    {
        $this->info("\n🔄 Simulating individual check-ins...");
        $bar = $this->output->createProgressBar(count($testData['ticket_ids']));
        
        $checkInJobs = [];
        foreach ($testData['ticket_ids'] as $index => $ticketId) {
            $scanner = $testData['scanners'][array_rand($testData['scanners'])];
            
            $checkInData = [
                'ticket_id' => $ticketId,
                'event_id' => Ticket::find($ticketId)?->event_id,
                'user_id' => $scanner->id,
                'gate_id' => 'Gate' . rand(1, 3),
                'device_id' => 'Device' . rand(1, 10),
                'scanned_at' => now()->subSeconds(rand(0, 60)),
            ];
            
            if ($realtime) {
                // Process immediately (synchronous)
                try {
                    $job = new ProcessTicketCheckIn($checkInData);
                    $job->handle();
                    $this->checkInsProcessed++;
                } catch (\Exception $e) {
                    $this->errors++;
                }
            } else {
                // Queue for async processing
                ProcessTicketCheckIn::dispatch($checkInData);
            }
            
            $bar->advance();
            
            // Simulate realistic scanning pace
            if ($index % 100 === 0) {
                usleep(10000); // 10ms pause every 100 scans
            }
        }
        
        $bar->finish();
        $this->newLine();
        
        if (!$realtime) {
            $this->info("📨 Jobs queued. Processing...");
            $this->processQueuedJobs();
        }
    }

    protected function runBatchTest($testData, $numScanners): void
    {
        $this->info("\n📦 Simulating batch check-ins...");
        
        $batchSize = 50; // Tickets per batch
        $batches = array_chunk($testData['ticket_ids'], $batchSize);
        $bar = $this->output->createProgressBar(count($batches));
        
        foreach ($batches as $batchIndex => $ticketBatch) {
            $scanner = $testData['scanners'][$batchIndex % $numScanners];
            
            $checkInsData = array_map(function ($ticketId) use ($scanner) {
                return [
                    'ticket_id' => $ticketId,
                    'event_id' => Ticket::find($ticketId)?->event_id,
                    'user_id' => $scanner->id,
                    'gate_id' => 'Gate' . rand(1, 3),
                    'device_id' => 'Device' . rand(1, 10),
                    'scanned_at' => now()->subSeconds(rand(0, 60)),
                ];
            }, $ticketBatch);
            
            // Queue batch job
            ProcessTicketCheckIn::dispatch($checkInsData, true);
            
            $bar->advance();
            
            // Simulate network delay between batches
            usleep(50000); // 50ms
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("📨 Batch jobs queued. Processing...");
        $this->processQueuedJobs();
    }

    protected function processQueuedJobs(): void
    {
        $startProcessing = microtime(true);
        
        // Get queue size
        $queueSize = Queue::size('check-ins');
        if ($queueSize === null) {
            $queueSize = Queue::size('default');
        }
        
        $this->info("📊 Queue size: $queueSize jobs");
        
        // Process queue with worker simulation
        $bar = $this->output->createProgressBar($queueSize ?: 100);
        $processed = 0;
        
        while (Queue::size('check-ins') > 0 || Queue::size('default') > 0) {
            // Simulate worker processing
            $job = Queue::pop('check-ins') ?? Queue::pop('default');
            if ($job) {
                try {
                    $job->fire();
                    $processed++;
                    $this->checkInsProcessed++;
                } catch (\Exception $e) {
                    $this->errors++;
                }
                $bar->advance();
            }
            
            // Break if taking too long
            if ((microtime(true) - $startProcessing) > 60) {
                $this->warn("\n⚠️  Processing timeout after 60 seconds");
                break;
            }
        }
        
        $bar->finish();
        $this->newLine();
        
        $processingTime = microtime(true) - $startProcessing;
        $this->info(sprintf("✅ Processed %d jobs in %.2f seconds", $processed, $processingTime));
    }

    protected function displayResults($totalRequests, $duration): void
    {
        // Get actual check-in stats from database
        $stats = DB::table('tickets')
            ->select(DB::raw("
                COUNT(CASE WHEN status = 'used' THEN 1 END) as checked_in,
                COUNT(CASE WHEN status = 'valid' THEN 1 END) as not_checked,
                COUNT(*) as total
            "))
            ->whereRaw("ticket_code LIKE 'TEST%'")
            ->first();
        
        $requestsPerSecond = $totalRequests / $duration;
        $avgResponseTime = ($duration * 1000) / $totalRequests;
        
        $this->newLine();
        $this->info("📊 LOAD TEST RESULTS");
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Requests', number_format($totalRequests)],
                ['Duration', sprintf('%.2f seconds', $duration)],
                ['Requests/Second', sprintf('%.2f', $requestsPerSecond)],
                ['Avg Response Time', sprintf('%.2f ms', $avgResponseTime)],
                ['Tickets Checked In', number_format($stats->checked_in ?? 0)],
                ['Success Rate', sprintf('%.2f%%', (($stats->checked_in ?? 0) / $totalRequests) * 100)],
                ['Errors', $this->errors],
            ]
        );
        
        // Performance rating
        $this->newLine();
        if ($requestsPerSecond > 1000) {
            $this->info("🚀 EXCELLENT: System can handle >1000 requests/second!");
        } elseif ($requestsPerSecond > 500) {
            $this->info("✅ GOOD: System can handle >500 requests/second");
        } elseif ($requestsPerSecond > 100) {
            $this->info("👍 ACCEPTABLE: System can handle >100 requests/second");
        } else {
            $this->warn("⚠️  NEEDS OPTIMIZATION: <100 requests/second");
        }
        
        // Capacity estimation
        $this->newLine();
        $this->info("📈 CAPACITY ESTIMATION:");
        $this->info(sprintf("  • Per minute: %s check-ins", number_format($requestsPerSecond * 60)));
        $this->info(sprintf("  • Per hour: %s check-ins", number_format($requestsPerSecond * 3600)));
        $this->info(sprintf("  • Per day: %s check-ins", number_format($requestsPerSecond * 86400)));
        
        // 1 million estimation
        $timeForMillion = 1000000 / $requestsPerSecond;
        $this->newLine();
        $this->info("🎯 Time to process 1 MILLION check-ins:");
        if ($timeForMillion < 3600) {
            $this->info(sprintf("  ⏱️  %.2f minutes", $timeForMillion / 60));
        } else {
            $this->info(sprintf("  ⏱️  %.2f hours", $timeForMillion / 3600));
        }
    }

    protected function cleanupTestData($testData): void
    {
        $this->info("\n🧹 Cleaning up test data...");
        
        // Delete test tickets
        DB::table('tickets')->whereIn('id', $testData['ticket_ids'])->delete();
        
        // Delete test events
        foreach ($testData['events'] as $event) {
            $event->delete();
        }
        
        // Delete test users
        foreach ($testData['scanners'] as $scanner) {
            if (str_contains($scanner->email, 'scanner') && str_contains($scanner->email, '@test.com')) {
                $scanner->delete();
            }
        }
        
        $this->info("✅ Test data cleaned up");
    }
}
