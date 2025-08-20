<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class Security extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Security & Roles';
    protected static ?int $navigationSort = 11;
    protected static string $view = 'filament.admin.pages.security';
    
    public function getHeading(): string
    {
        return 'Security & Roles';
    }
    
    public function getSubheading(): ?string
    {
        return 'Audit logs, admin roles & permissions, API key management';
    }
}