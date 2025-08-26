<?php

namespace App\Filament\Organizer\Resources\PayoutResource\Pages;

use App\Filament\Organizer\Resources\PayoutResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('requestPayout')
                ->label('Request Payout')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->url(route('filament.organizer.pages.payout-request')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PayoutResource\Widgets\PayoutStats::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => $this->getModel()::where('organizer_id', auth()->user()->organizer->id)
                    ->where('status', 'pending')->count()),
            'processing' => Tab::make('Processing')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['approved', 'processing'])),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['completed', 'paid'])),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
        ];
    }
}
