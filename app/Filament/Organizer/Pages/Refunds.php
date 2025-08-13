<?php

namespace App\Filament\Organizer\Pages;

use Filament\Pages\Page;

class Refunds extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';
    
    protected static ?string $navigationLabel = 'Refunds';
    
    protected static ?int $navigationSort = 6;
    
    protected static string $view = 'filament.organizer.pages.refunds';
    
    public function getHeading(): string
    {
        return 'Refunds';
    }
    
    public function getSubheading(): ?string
    {
        return 'Manage refund requests and history';
    }
}