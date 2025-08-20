<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Settings';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.admin.pages.settings';
    
    public function getHeading(): string
    {
        return 'Platform Settings';
    }
    
    public function getSubheading(): ?string
    {
        return 'Configure platform, payment gateways, and system health';
    }
}