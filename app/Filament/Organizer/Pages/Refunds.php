<?php

namespace App\Filament\Organizer\Pages;

use Filament\Pages\Page;

class Refunds extends Page
{
    protected static bool $shouldRegisterNavigation = false; // Hide from navigation since we're using the resource

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationLabel = 'Refunds';

    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.organizer.pages.refunds';

    public function mount(): void
    {
        // Redirect to the RefundRequestResource
        redirect()->to('/organizer/dashboard/refund-requests');
    }

    public function getHeading(): string
    {
        return 'Refunds';
    }

    public function getSubheading(): ?string
    {
        return 'Manage refund requests and history';
    }
}
