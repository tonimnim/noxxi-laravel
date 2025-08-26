<?php

namespace App\Filament\Organizer\Resources\PayoutResource\Pages;

use App\Filament\Organizer\Resources\PayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPayout extends ViewRecord
{
    protected static string $resource = PayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadReceipt')
                ->label('Download Receipt')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['completed', 'paid']))
                ->url(fn () => route('organizer.payout.receipt', $this->record->id))
                ->openUrlInNewTab(),

            Actions\Action::make('back')
                ->label('Back to List')
                ->url(PayoutResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
