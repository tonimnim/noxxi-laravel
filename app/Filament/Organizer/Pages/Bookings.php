<?php

namespace App\Filament\Organizer\Pages;

use Filament\Pages\Page;

class Bookings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationLabel = 'Bookings';
    
    protected static ?int $navigationSort = 3;
    
    protected static string $view = 'filament.organizer.pages.bookings';
    
    public function getHeading(): string
    {
        return 'Bookings';
    }
    
    public function getSubheading(): ?string
    {
        return 'View and manage customer bookings';
    }
}