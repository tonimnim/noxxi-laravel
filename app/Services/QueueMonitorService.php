<?php

namespace App\Services;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class QueueMonitorService
{
    /**
     * Get queue statistics
     */
    public function getQueueStats(): array
    {
        try {
            $redis = Redis::connection();

            $queues = ['default', 'high', 'low'];
            $stats = [];

            foreach ($queues as $queue) {
                $queueKey = "queues:{$queue}";
                $processingKey = "queues:{$queue}:processing";
                $delayedKey = "queues:{$queue}:delayed";
                $reservedKey = "queues:{$queue}:reserved";

                $stats[$queue] = [
                    'pending' => $redis->llen($queueKey),
                    'processing' => $redis->zcount($processingKey, '-inf', '+inf'),
                    'delayed' => $redis->zcount($delayedKey, '-inf', '+inf'),
                    'reserved' => $redis->zcount($reservedKey, '-inf', '+inf'),
                ];
            }

            // Get failed jobs count
            $stats['failed'] = $redis->llen('failed_jobs');

            // Get worker status
            $stats['workers'] = $this->getWorkerStatus();

            return $stats;
        } catch (\Exception $e) {
            \Log::error('Failed to get queue stats', ['error' => $e->getMessage()]);

            return [
                'error' => 'Unable to fetch queue statistics',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get worker status
     */
    protected function getWorkerStatus(): array
    {
        try {
            $redis = Redis::connection();
            $workers = [];

            // Get all worker keys
            $workerKeys = $redis->keys('worker:*');

            foreach ($workerKeys as $key) {
                $workerInfo = $redis->get($key);
                if ($workerInfo) {
                    $workers[] = json_decode($workerInfo, true);
                }
            }

            return [
                'active' => count($workers),
                'list' => $workers,
            ];
        } catch (\Exception $e) {
            return [
                'active' => 0,
                'list' => [],
            ];
        }
    }

    /**
     * Clear failed jobs
     */
    public function clearFailedJobs(): bool
    {
        try {
            $redis = Redis::connection();
            $redis->del('failed_jobs');

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to clear failed jobs', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Retry failed jobs
     */
    public function retryFailedJobs(): int
    {
        try {
            $redis = Redis::connection();
            $failedJobs = $redis->lrange('failed_jobs', 0, -1);
            $retried = 0;

            foreach ($failedJobs as $failedJob) {
                $job = json_decode($failedJob, true);

                if (isset($job['payload'])) {
                    // Re-dispatch the job
                    Queue::pushRaw($job['payload'], $job['queue'] ?? 'default');
                    $retried++;
                }
            }

            // Clear failed jobs after retrying
            if ($retried > 0) {
                $this->clearFailedJobs();
            }

            return $retried;
        } catch (\Exception $e) {
            \Log::error('Failed to retry failed jobs', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Get queue size for a specific queue
     */
    public function getQueueSize(string $queue = 'default'): int
    {
        try {
            $redis = Redis::connection();

            return $redis->llen("queues:{$queue}");
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check if queue workers are running
     */
    public function areWorkersRunning(): bool
    {
        $workers = $this->getWorkerStatus();

        return $workers['active'] > 0;
    }

    /**
     * Get job processing metrics
     */
    public function getMetrics(int $hours = 24): array
    {
        try {
            $redis = Redis::connection();
            $now = time();
            $since = $now - ($hours * 3600);

            // Get job metrics from cache
            $metrics = [];
            $metricsKey = 'queue:metrics:'.date('Y-m-d');

            if ($redis->exists($metricsKey)) {
                $metrics = json_decode($redis->get($metricsKey), true);
            }

            return [
                'period' => "{$hours} hours",
                'processed' => $metrics['processed'] ?? 0,
                'failed' => $metrics['failed'] ?? 0,
                'avg_processing_time' => $metrics['avg_time'] ?? 0,
                'peak_queue_size' => $metrics['peak_size'] ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'period' => "{$hours} hours",
                'processed' => 0,
                'failed' => 0,
                'avg_processing_time' => 0,
                'peak_queue_size' => 0,
            ];
        }
    }
}
