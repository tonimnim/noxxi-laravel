<?php

namespace App\Filament\Organizer\Resources\RefundRequestResource\Pages;

use App\Filament\Organizer\Resources\RefundRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRefundRequests extends ListRecords
{
    protected static string $resource = RefundRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refund_policy')
                ->label('View Refund Policy')
                ->icon('heroicon-o-information-circle')
                ->modalHeading('Refund Policy Guidelines')
                ->modalContent(view('filament.organizer.modals.refund-policy'))
                ->modalWidth('lg')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RefundRequestResource\Widgets\RefundStats::class,
        ];
    }
}
