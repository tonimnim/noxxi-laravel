<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class Support extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Support';
    protected static ?int $navigationSort = 7;
    protected static string $view = 'filament.admin.pages.support';
    
    public static function getNavigationBadge(): ?string
    {
        // Return count of open support tickets
        return cache()->remember('admin.support.open_tickets', 60, function () {
            // TODO: Implement when SupportTicket model is created
            return null;
        });
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'danger' : null;
    }
    
    public function getHeading(): string
    {
        return 'Support & Disputes';
    }
    
    public function getSubheading(): ?string
    {
        return 'Manage support tickets, disputes, and user reports';
    }
}