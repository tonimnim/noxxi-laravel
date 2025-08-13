<?php

namespace App\Filament\Organizer\Pages;

use Filament\Pages\Page;

class Payouts extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'Payouts';
    
    protected static ?int $navigationSort = 5;
    
    protected static string $view = 'filament.organizer.pages.payouts';
    
    public function getHeading(): string
    {
        return 'Payouts';
    }
    
    public function getSubheading(): ?string
    {
        return 'View your payment history and pending payouts';
    }
}