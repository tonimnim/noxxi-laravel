<?php

namespace App\Filament\Admin\Widgets\System;

use App\Services\ActivityService;
use App\Services\HealthMetricsService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class PlatformHealthMonitor extends Widget
{
    protected static string $view = 'filament.admin.widgets.system.platform-health-monitor';
    
    protected static ?int $sort = 1;
    
    // Full width across both columns
    protected int | string | array $columnSpan = 2;
    
    // Lazy load for performance
    protected static bool $isLazy = true;
    
    // No auto polling to prevent performance issues
    protected static ?string $pollingInterval = null;
    
    protected HealthMetricsService $healthService;
    
    public function mount(): void
    {
        $this->healthService = app(HealthMetricsService::class);
    }
    
    protected function getViewData(): array
    {
        try {
            // Get all metrics from service (already cached)
            $allMetrics = $this->healthService->getAllMetrics();
            
            // Flatten metrics for display
            $displayMetrics = [];
            
            // Database metrics
            if (isset($allMetrics['database'])) {
                foreach ($allMetrics['database'] as $key => $metric) {
                    $displayMetrics['db_' . $key] = $metric;
                }
            }
            
            // Application metrics
            if (isset($allMetrics['application'])) {
                foreach ($allMetrics['application'] as $key => $metric) {
                    $displayMetrics['app_' . $key] = $metric;
                }
            }
            
            // System metrics (selected ones)
            if (isset($allMetrics['system'])) {
                $displayMetrics['sys_memory'] = $allMetrics['system']['memory'] ?? null;
                $displayMetrics['sys_disk'] = $allMetrics['system']['disk'] ?? null;
            }
            
            // Check for critical issues and log them
            if ($this->healthService->hasCriticalIssues()) {
                // Log critical health issues (throttled to once per hour)
                Cache::remember('health.critical.logged', 3600, function () {
                    ActivityService::logSystem(
                        'alert',
                        'Critical system health issues detected',
                        ['metrics' => $this->healthService->getAllMetrics()]
                    );
                    return true;
                });
            }
            
            Cache::put('admin.system.health.last_update', now(), 30);
            
            return [
                'isLoading' => false,
                'metrics' => $displayMetrics,
                'hasCritical' => $this->healthService->hasCriticalIssues(),
                'lastUpdate' => now(),
            ];
        } catch (\Exception $e) {
            // Return safe defaults if metrics fail
            return [
                'isLoading' => false,
                'metrics' => [
                    'error' => [
                        'status' => 'unknown',
                        'label' => 'System Status',
                        'value' => 'Unable to fetch metrics',
                    ],
                ],
                'hasCritical' => false,
                'lastUpdate' => now(),
            ];
        }
    }
}