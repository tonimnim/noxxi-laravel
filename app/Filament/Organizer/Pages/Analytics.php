<?php

namespace App\Filament\Organizer\Pages;

use Filament\Pages\Page;

class Analytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'Analytics & Reports';
    
    protected static ?int $navigationSort = 7;
    
    protected static string $view = 'filament.organizer.pages.analytics';
    
    public function getHeading(): string
    {
        return 'Analytics & Reports';
    }
    
    public function getSubheading(): ?string
    {
        return 'View insights and generate reports';
    }
}