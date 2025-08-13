<?php

namespace App\Filament\Organizer\Resources;

use App\Filament\Organizer\Resources\EventResource\Forms\EventForm;
use App\Filament\Organizer\Resources\EventResource\Pages;
use App\Filament\Organizer\Resources\EventResource\Tables\EventTable;
use App\Models\Event;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    protected static ?string $navigationLabel = 'Listings';
    
    protected static ?string $modelLabel = 'Listing';
    
    protected static ?string $pluralModelLabel = 'Listings';
    
    protected static ?string $slug = 'listings';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return EventForm::form($form);
    }

    public static function table(Table $table): Table
    {
        return EventTable::table($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('organizer_id', Auth::user()->organizer?->id)
            ->with(['category', 'paidBookings'])
            ->withCount('paidBookings');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
            'view' => Pages\ViewEvent::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $organizerId = Auth::user()->organizer?->id;
        if (!$organizerId) {
            return null;
        }
        
        return cache()->remember(
            "organizer.{$organizerId}.published_events_count",
            now()->addMinutes(5),
            fn () => static::getModel()::where('organizer_id', $organizerId)
                ->where('status', 'published')
                ->count()
        );
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}