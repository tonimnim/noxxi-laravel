<?php

namespace App\Filament\Organizer\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationLabel = 'Dashboard';

    // Just override getHeading to return empty - that's all we need
    public function getHeading(): string|Htmlable
    {
        return '';
    }

    protected function getViewData(): array
    {
        $organizer = Auth::user()->organizer;

        if ($organizer && ! $organizer->is_verified) {
            Notification::make()
                ->warning()
                ->title('Account Pending Verification')
                ->body('Your organizer account is awaiting admin approval. You will be notified once your account is verified and you can start creating listings.')
                ->persistent()
                ->send();
        }

        return parent::getViewData();
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Organizer\Widgets\StatsOverview::class,
            \App\Filament\Organizer\Widgets\ListingsBookingsWidget::class,
            \App\Filament\Organizer\Widgets\RecentActivityFeed::class,
            \App\Filament\Organizer\Widgets\PayoutsSummary::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
            'xl' => 2,
            '2xl' => 2,
        ];
    }
}
