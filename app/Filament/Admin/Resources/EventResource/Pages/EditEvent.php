<?php

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Filament\Admin\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggleFeatured')
                ->label(fn ($record) => $record->featured ? 'Featured ✓' : 'Not Featured')
                ->color(fn ($record) => $record->featured ? 'success' : 'gray')
                ->action(function ($record) {
                    $record->update(['featured' => !$record->featured]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title($record->featured ? 'Event featured' : 'Event unfeatured')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
