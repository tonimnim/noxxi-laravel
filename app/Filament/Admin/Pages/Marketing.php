<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class Marketing extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Marketing';
    protected static ?int $navigationSort = 9;
    protected static string $view = 'filament.admin.pages.marketing';
    
    public function getHeading(): string
    {
        return 'Marketing & Campaigns';
    }
    
    public function getSubheading(): ?string
    {
        return 'Campaign management, discount codes, and broadcasts';
    }
}