<?php

namespace App\Filament\Organizer\Pages;

use Filament\Pages\Page;

class Revenue extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    
    protected static ?string $navigationLabel = 'Revenue';
    
    protected static ?int $navigationSort = 4;
    
    protected static string $view = 'filament.organizer.pages.revenue';
    
    public function getHeading(): string
    {
        return 'Revenue';
    }
    
    public function getSubheading(): ?string
    {
        return 'Track your earnings and transactions';
    }
}