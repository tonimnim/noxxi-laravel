<?php

namespace App\Filament\Admin\Widgets\System;

use App\Models\SystemAnnouncement;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemAnnouncements extends Widget
{
    protected static string $view = 'filament.admin.widgets.system.system-announcements';
    
    protected static ?int $sort = 2;
    
    // Takes one column in 2-column layout
    protected int | string | array $columnSpan = 1;
    
    // Lazy load for performance
    protected static bool $isLazy = true;
    
    // No auto polling
    protected static ?string $pollingInterval = null;
    
    protected function getViewData(): array
    {
        // Cache for 10 minutes - announcements don't change frequently
        $announcements = Cache::remember('admin.system.announcements.list', 600, function () {
            // Single optimized query with no joins, using composite index
            return DB::table('system_announcements')
                ->select([
                    'id',
                    'type',
                    'title',
                    'message',
                    'priority',
                    'scheduled_for',
                    'expires_at',
                    'created_at'
                ])
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('scheduled_for')
                          ->orWhere('scheduled_for', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->orderByRaw("
                    CASE priority
                        WHEN 'critical' THEN 1
                        WHEN 'high' THEN 2
                        WHEN 'medium' THEN 3
                        WHEN 'low' THEN 4
                    END
                ")
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($announcement) {
                    // Map to display format
                    return [
                        'id' => $announcement->id,
                        'title' => $announcement->title,
                        'message' => $announcement->message,
                        'type' => $announcement->type,
                        'priority' => $announcement->priority,
                        'icon' => $this->getIcon($announcement->type),
                        'color' => $this->getColor($announcement->priority),
                        'time' => $this->getFormattedTime($announcement),
                    ];
                })
                ->toArray();
        });
        
        Cache::put('admin.system.announcements.last_update', now(), 600);
        
        return [
            'isLoading' => false,
            'announcements' => $announcements,
            'hasAnnouncements' => count($announcements) > 0,
        ];
    }
    
    private function getIcon(string $type): string
    {
        return match($type) {
            'maintenance' => 'wrench-screwdriver',
            'update' => 'arrow-path',
            'alert' => 'exclamation-triangle',
            'info' => 'information-circle',
            default => 'megaphone',
        };
    }
    
    private function getColor(string $priority): string
    {
        return match($priority) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'blue',
            default => 'gray',
        };
    }
    
    private function getFormattedTime($announcement): string
    {
        $createdAt = \Carbon\Carbon::parse($announcement->created_at);
        $scheduledFor = $announcement->scheduled_for ? \Carbon\Carbon::parse($announcement->scheduled_for) : null;
        
        if ($scheduledFor && $scheduledFor->isFuture()) {
            return 'Scheduled for ' . $scheduledFor->format('M d, H:i');
        }
        
        return $createdAt->diffForHumans();
    }
}