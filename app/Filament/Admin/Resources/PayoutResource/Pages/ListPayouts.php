<?php
namespace App\Filament\Admin\Resources\PayoutResource\Pages;
use App\Filament\Admin\Resources\PayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;
    
    protected function getHeaderActions(): array
    {
        return [];
    }
    
    public function getTabs(): array
    {
        return [
            'unprocessed' => Tab::make('Unprocessed')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['pending', 'on_hold']))
                ->badge(fn () => \App\Models\Payout::whereIn('status', ['pending', 'on_hold'])->count())
                ->badgeColor('warning'),
                
            'processed' => Tab::make('Processed')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['approved', 'processing', 'completed', 'rejected', 'failed']))
                ->badge(fn () => \App\Models\Payout::whereIn('status', ['approved', 'processing', 'completed', 'rejected', 'failed'])->count()),
        ];
    }
    
    public function getDefaultActiveTab(): string
    {
        return 'unprocessed';
    }
}
