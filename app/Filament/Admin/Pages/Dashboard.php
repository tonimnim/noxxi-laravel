<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Dashboard';
    
    public function getHeading(): string
    {
        return '';
    }
    
    public function getSubheading(): ?string
    {
        return null;
    }
    
    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 4,
        ];
    }
    
    public function getWidgets(): array
    {
        return [
            // Phase 1: Core Essentials
            \App\Filament\Admin\Widgets\AdminStatsOverview::class,
            \App\Filament\Admin\Widgets\RevenueChart::class,
            \App\Filament\Admin\Widgets\PendingActions::class,
            
            // Phase 2: Activity Monitoring
            \App\Filament\Admin\Widgets\LiveActivityFeed::class,
            // \App\Filament\Admin\Widgets\PlatformHealthMonitor::class,
            
            // Phase 3: Analytics (To be enabled later)
            // \App\Filament\Admin\Widgets\TopPerformers::class,
            // \App\Filament\Admin\Widgets\GeographicHeatMap::class,
        ];
    }
    
    public function getWidgetData(): array
    {
        return [];
    }
}