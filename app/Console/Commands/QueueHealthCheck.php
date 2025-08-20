<?php

namespace App\Console\Commands;

use App\Services\QueueMonitorService;
use Illuminate\Console\Command;

class QueueHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:health 
                            {--detailed : Show detailed queue information}
                            {--fix : Attempt to fix issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check queue health and worker status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $monitor = new QueueMonitorService;

        $this->info('🔍 Checking Queue Health...');
        $this->newLine();

        // Get queue statistics
        $stats = $monitor->getQueueStats();

        if (isset($stats['error'])) {
            $this->error("❌ {$stats['error']}");
            $this->error("   {$stats['message']}");

            return Command::FAILURE;
        }

        // Display queue status
        $this->displayQueueStatus($stats);

        // Check for issues
        $issues = $this->checkForIssues($stats, $monitor);

        if (! empty($issues)) {
            $this->newLine();
            $this->warn('⚠️  Issues Detected:');
            foreach ($issues as $issue) {
                $this->warn("   • {$issue}");
            }

            if ($this->option('fix')) {
                $this->newLine();
                $this->info('🔧 Attempting to fix issues...');
                $this->fixIssues($monitor, $issues);
            }
        } else {
            $this->newLine();
            $this->info('✅ Queue system is healthy!');
        }

        if ($this->option('detailed')) {
            $this->newLine();
            $this->displayDetailedMetrics($monitor);
        }

        return Command::SUCCESS;
    }

    /**
     * Display queue status
     */
    protected function displayQueueStatus(array $stats): void
    {
        $this->info('📊 Queue Status:');
        $this->table(
            ['Queue', 'Pending', 'Processing', 'Delayed', 'Reserved'],
            [
                ['default', $stats['default']['pending'], $stats['default']['processing'], $stats['default']['delayed'], $stats['default']['reserved']],
                ['high', $stats['high']['pending'], $stats['high']['processing'], $stats['high']['delayed'], $stats['high']['reserved']],
                ['low', $stats['low']['pending'], $stats['low']['processing'], $stats['low']['delayed'], $stats['low']['reserved']],
            ]
        );

        $this->newLine();
        $this->info("👷 Workers: {$stats['workers']['active']} active");
        $this->info("❌ Failed Jobs: {$stats['failed']}");
    }

    /**
     * Check for issues
     */
    protected function checkForIssues(array $stats, QueueMonitorService $monitor): array
    {
        $issues = [];

        // Check if workers are running
        if ($stats['workers']['active'] === 0) {
            $issues[] = 'No queue workers are running';
        }

        // Check for high failure rate
        if ($stats['failed'] > 100) {
            $issues[] = "High number of failed jobs ({$stats['failed']})";
        }

        // Check for stuck jobs
        $totalProcessing = $stats['default']['processing'] + $stats['high']['processing'] + $stats['low']['processing'];
        if ($totalProcessing > 50) {
            $issues[] = "Many jobs stuck in processing ({$totalProcessing})";
        }

        // Check for queue backlog
        $totalPending = $stats['default']['pending'] + $stats['high']['pending'] + $stats['low']['pending'];
        if ($totalPending > 1000) {
            $issues[] = "Large queue backlog ({$totalPending} pending jobs)";
        }

        // Check high priority queue
        if ($stats['high']['pending'] > 100) {
            $issues[] = "High priority queue backlog ({$stats['high']['pending']} jobs)";
        }

        return $issues;
    }

    /**
     * Fix detected issues
     */
    protected function fixIssues(QueueMonitorService $monitor, array $issues): void
    {
        foreach ($issues as $issue) {
            if (str_contains($issue, 'No queue workers')) {
                $this->warn('   ⚡ Start queue workers with: php artisan queue:work --queue=high,default,low');
                $this->warn('   Or use: composer run dev (includes queue worker)');
            }

            if (str_contains($issue, 'failed jobs')) {
                if ($this->confirm('Retry failed jobs?')) {
                    $retried = $monitor->retryFailedJobs();
                    $this->info("   ✅ Retried {$retried} failed jobs");
                }
            }

            if (str_contains($issue, 'stuck in processing')) {
                $this->warn('   ⚡ Consider restarting queue workers: php artisan queue:restart');
            }

            if (str_contains($issue, 'queue backlog')) {
                $this->warn('   ⚡ Scale up workers or optimize job processing');
            }
        }
    }

    /**
     * Display detailed metrics
     */
    protected function displayDetailedMetrics(QueueMonitorService $monitor): void
    {
        $this->info('📈 24-Hour Metrics:');
        $metrics = $monitor->getMetrics(24);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Period', $metrics['period']],
                ['Jobs Processed', number_format($metrics['processed'])],
                ['Jobs Failed', number_format($metrics['failed'])],
                ['Avg Processing Time', $metrics['avg_processing_time'].' seconds'],
                ['Peak Queue Size', number_format($metrics['peak_queue_size'])],
            ]
        );
    }
}
