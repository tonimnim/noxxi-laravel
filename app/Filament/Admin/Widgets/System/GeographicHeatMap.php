<?php

namespace App\Filament\Admin\Widgets\System;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class GeographicHeatMap extends Widget
{
    protected static string $view = 'filament.admin.widgets.system.geographic-heat-map';
    
    protected static ?int $sort = 3;
    
    // Right side - half width
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'md' => 1,
        'lg' => 1,
        'xl' => 1,
    ];
    
    // Lazy load for performance
    protected static bool $isLazy = true;
    
    // No auto polling
    protected static ?string $pollingInterval = null;
    
    protected function getViewData(): array
    {
        // For Phase 1, return empty data with loading skeleton
        Cache::put('admin.system.heatmap.last_update', now(), 1800); // 30 minutes
        
        return [
            'isLoading' => true,
            'mapData' => [],
            'topCountries' => [],
            'totalCountries' => 0,
        ];
    }
}