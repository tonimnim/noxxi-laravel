<?php

namespace App\Filament\Organizer\Resources\EventResource\Pages;

use App\Filament\Organizer\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    public function getHeading(): string|Htmlable
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
            Actions\Action::make('export')
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->outlined()
                ->action(function () {
                    // Export logic
                }),
            Actions\CreateAction::make()
                ->label('Create listing')
                ->icon('heroicon-o-plus'),
        ];
    }
}