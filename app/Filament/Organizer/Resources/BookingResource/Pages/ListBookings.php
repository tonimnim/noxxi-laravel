<?php

namespace App\Filament\Organizer\Resources\BookingResource\Pages;

use App\Filament\Organizer\Resources\BookingResource;
use App\Filament\Organizer\Resources\BookingResource\Widgets\BookingStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\Action::make('filter_today')
                    ->label('Today')
                    ->action(function () {
                        $this->tableFilters['date']['from'] = now()->format('Y-m-d');
                        $this->tableFilters['date']['until'] = now()->format('Y-m-d');
                    }),
                Actions\Action::make('filter_week')
                    ->label('This Week')
                    ->action(function () {
                        $this->tableFilters['date']['from'] = now()->startOfWeek()->format('Y-m-d');
                        $this->tableFilters['date']['until'] = now()->endOfWeek()->format('Y-m-d');
                    }),
                Actions\Action::make('filter_month')
                    ->label('This Month')
                    ->action(function () {
                        $this->tableFilters['date']['from'] = now()->startOfMonth()->format('Y-m-d');
                        $this->tableFilters['date']['until'] = now()->endOfMonth()->format('Y-m-d');
                    }),
            ])
                ->label('Quick Filters')
                ->icon('heroicon-m-funnel')
                ->color('gray')
                ->button(),

            Actions\Action::make('export')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => $this->exportAll()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BookingStats::class,
        ];
    }

    protected function exportAll(): void
    {
        // Implementation for export functionality
        // This would generate a CSV/Excel file with all bookings
    }
}
