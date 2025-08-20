<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QueueMonitorService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SystemHealthController extends Controller
{
    use ApiResponse;

    /**
     * Get system health status
     */
    public function health(): JsonResponse
    {
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => now()->toIso8601String(),
                'checks' => $this->performHealthChecks(),
            ];

            // Determine overall status
            $hasErrors = collect($health['checks'])->contains('status', 'error');
            $hasWarnings = collect($health['checks'])->contains('status', 'warning');

            if ($hasErrors) {
                $health['status'] = 'unhealthy';

                return $this->error('System unhealthy', 503, $health);
            }

            if ($hasWarnings) {
                $health['status'] = 'degraded';
            }

            return $this->success($health);
        } catch (\Exception $e) {
            return $this->error('Health check failed', 500);
        }
    }

    /**
     * Get detailed system metrics
     */
    public function metrics(): JsonResponse
    {
        try {
            // Cache metrics for 1 minute to reduce load
            $metrics = Cache::remember('system_metrics', 60, function () {
                return [
                    'database' => $this->getDatabaseMetrics(),
                    'cache' => $this->getCacheMetrics(),
                    'queue' => $this->getQueueMetrics(),
                    'api' => $this->getApiMetrics(),
                ];
            });

            return $this->success($metrics);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch metrics', 500);
        }
    }

    /**
     * Perform health checks
     */
    protected function performHealthChecks(): array
    {
        $checks = [];

        // Database check
        $checks['database'] = $this->checkDatabase();

        // Redis/Cache check
        $checks['cache'] = $this->checkCache();

        // Queue check
        $checks['queue'] = $this->checkQueue();

        // Storage check
        $checks['storage'] = $this->checkStorage();

        return $checks;
    }

    /**
     * Check database health
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => $latency < 100 ? 'healthy' : 'warning',
                'latency' => $latency.'ms',
                'message' => 'Database connection OK',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache/Redis health
     */
    protected function checkCache(): array
    {
        try {
            $start = microtime(true);
            Cache::put('health_check', true, 10);
            $value = Cache::get('health_check');
            Cache::forget('health_check');
            $latency = round((microtime(true) - $start) * 1000, 2);

            if (! $value) {
                throw new \Exception('Cache read/write failed');
            }

            return [
                'status' => $latency < 50 ? 'healthy' : 'warning',
                'latency' => $latency.'ms',
                'message' => 'Cache connection OK',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue health
     */
    protected function checkQueue(): array
    {
        try {
            $monitor = new QueueMonitorService;
            $stats = $monitor->getQueueStats();

            if (isset($stats['error'])) {
                throw new \Exception($stats['error']);
            }

            $workersActive = $stats['workers']['active'] > 0;
            $highQueueBacklog = $stats['high']['pending'] > 100;
            $failedJobs = $stats['failed'];

            $status = 'healthy';
            $messages = [];

            if (! $workersActive) {
                $status = 'warning';
                $messages[] = 'No queue workers active';
            }

            if ($highQueueBacklog) {
                $status = 'warning';
                $messages[] = 'High priority queue backlog';
            }

            if ($failedJobs > 100) {
                $status = 'error';
                $messages[] = 'High number of failed jobs';
            }

            return [
                'status' => $status,
                'workers' => $stats['workers']['active'],
                'failed_jobs' => $failedJobs,
                'message' => empty($messages) ? 'Queue system OK' : implode(', ', $messages),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Queue check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage health
     */
    protected function checkStorage(): array
    {
        try {
            $path = storage_path('app');

            if (! is_writable($path)) {
                throw new \Exception('Storage not writable');
            }

            // Check disk space (require at least 100MB free)
            $freeSpace = disk_free_space($path);
            $totalSpace = disk_total_space($path);
            $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);

            $status = 'healthy';
            if ($freeSpace < 104857600) { // Less than 100MB
                $status = 'error';
            } elseif ($usedPercent > 90) {
                $status = 'warning';
            }

            return [
                'status' => $status,
                'free_space' => $this->formatBytes($freeSpace),
                'used_percent' => $usedPercent.'%',
                'message' => 'Storage OK',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get database metrics
     */
    protected function getDatabaseMetrics(): array
    {
        try {
            $connectionCount = DB::select('SELECT count(*) as count FROM pg_stat_activity WHERE datname = ?', [env('DB_DATABASE')])[0]->count ?? 0;

            return [
                'connections' => $connectionCount,
                'max_connections' => config('database.connections.pgsql.max_connections', 100),
                'slow_queries' => Cache::get('slow_queries_count', 0),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to fetch database metrics'];
        }
    }

    /**
     * Get cache metrics
     */
    protected function getCacheMetrics(): array
    {
        try {
            $redis = Redis::connection();
            $info = $redis->info();

            return [
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'memory_peak' => $info['used_memory_peak_human'] ?? 'N/A',
                'keys' => $info['db0']['keys'] ?? 0,
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => $this->calculateHitRate($info['keyspace_hits'] ?? 0, $info['keyspace_misses'] ?? 0),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to fetch cache metrics'];
        }
    }

    /**
     * Get queue metrics
     */
    protected function getQueueMetrics(): array
    {
        try {
            $monitor = new QueueMonitorService;
            $stats = $monitor->getQueueStats();
            $metrics = $monitor->getMetrics(24);

            if (isset($stats['error'])) {
                throw new \Exception($stats['error']);
            }

            return [
                'pending_jobs' => [
                    'high' => $stats['high']['pending'],
                    'default' => $stats['default']['pending'],
                    'low' => $stats['low']['pending'],
                ],
                'failed_jobs' => $stats['failed'],
                'workers_active' => $stats['workers']['active'],
                '24h_processed' => $metrics['processed'],
                '24h_failed' => $metrics['failed'],
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to fetch queue metrics'];
        }
    }

    /**
     * Get API metrics
     */
    protected function getApiMetrics(): array
    {
        return [
            'requests_per_minute' => Cache::get('api_requests_per_minute', 0),
            'avg_response_time' => Cache::get('api_avg_response_time', 0).'ms',
            'error_rate' => Cache::get('api_error_rate', 0).'%',
            'rate_limit_hits' => Cache::get('rate_limit_hits', 0),
        ];
    }

    /**
     * Calculate cache hit rate
     */
    protected function calculateHitRate(int $hits, int $misses): string
    {
        $total = $hits + $misses;
        if ($total === 0) {
            return '0%';
        }

        return round(($hits / $total) * 100, 2).'%';
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
