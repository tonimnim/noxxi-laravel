<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class Analytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Analytics & Reports';
    protected static ?int $navigationSort = 8;
    protected static string $view = 'filament.admin.pages.analytics';
    
    public function getHeading(): string
    {
        return 'Analytics & Reports';
    }
    
    public function getSubheading(): ?string
    {
        return 'Platform insights, revenue reports, and export center';
    }
}