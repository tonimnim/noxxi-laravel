<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class System extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?int $navigationSort = 8; // After Analytics & Reports (7), before Settings (9)
    
    protected static ?string $title = 'System';
    
    protected static string $view = 'filament.admin.pages.system';
    
    protected static ?string $navigationLabel = 'System';
    
    protected static ?string $slug = 'system';
    
    public function getHeading(): string
    {
        return '';
    }
    
    public function getSubheading(): ?string
    {
        return null;
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            // No header widgets - all widgets in main section
        ];
    }
    
    public function hasHeaderWidgets(): bool
    {
        return count($this->getHeaderWidgets()) > 0;
    }
    
    protected function getWidgets(): array
    {
        return [
            // Platform Health Monitor at the top (full width)
            \App\Filament\Admin\Widgets\System\PlatformHealthMonitor::class,
            // System Announcements and Geographic Heat Map below
            \App\Filament\Admin\Widgets\System\SystemAnnouncements::class,
            \App\Filament\Admin\Widgets\System\GeographicHeatMapWidget::class,
        ];
    }
    
    public function getColumns(): int | string | array
    {
        return 2;
    }
    
    public static function getNavigationBadge(): ?string
    {
        // Cache critical alerts count for 2 minutes
        return Cache::remember('admin.system.critical_alerts', 120, function () {
            $count = \DB::table('system_announcements')
                ->where('is_active', true)
                ->where('priority', 'critical')
                ->where(function ($query) {
                    $query->whereNull('scheduled_for')
                          ->orWhere('scheduled_for', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->count();
            
            return $count > 0 ? (string) $count : null;
        });
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
    
    public function getWidgetData(): array
    {
        return [];
    }
}