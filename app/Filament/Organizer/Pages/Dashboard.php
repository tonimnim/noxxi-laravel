<?php

namespace App\Filament\Organizer\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = -2;
    protected static ?string $navigationLabel = 'Dashboard';
    
    // Just override getHeading to return empty - that's all we need
    public function getHeading(): string|Htmlable
    {
        return '';
    }
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Organizer\Widgets\StatsOverview::class,
            \App\Filament\Organizer\Widgets\ListingsBookingsWidget::class,
            \App\Filament\Organizer\Widgets\RecentActivityFeed::class,
            \App\Filament\Organizer\Widgets\PayoutsSummary::class,
        ];
    }
    
    public function getColumns(): int|string|array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
            'xl' => 2,
            '2xl' => 2,
        ];
    }
}