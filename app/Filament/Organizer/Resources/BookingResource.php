<?php

namespace App\Filament\Organizer\Resources;

use App\Filament\Organizer\Resources\BookingResource\Pages;
use App\Filament\Organizer\Resources\BookingResource\Tables\BookingTable;
use App\Models\Booking;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Bookings';

    protected static ?string $modelLabel = 'Booking';

    protected static ?string $pluralModelLabel = 'Bookings';

    protected static ?string $slug = 'bookings';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'booking_reference';

    public static function form(Form $form): Form
    {
        // Forms are typically not needed for bookings as they're created by customers
        // This would be used only for admin editing if needed
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return BookingTable::table($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('event', function ($query) {
                $query->where('organizer_id', Auth::user()->organizer?->id);
            })
            ->with(['event', 'user', 'tickets'])
            ->latest();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'view' => Pages\ViewBooking::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $organizerId = Auth::user()->organizer?->id;
        if (! $organizerId) {
            return null;
        }

        return cache()->remember(
            "organizer.{$organizerId}.pending_bookings_count",
            now()->addMinute(),
            fn () => static::getModel()::whereHas('event', function ($query) use ($organizerId) {
                $query->where('organizer_id', $organizerId);
            })
                ->where('payment_status', 'processing')
                ->count()
        );
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pending payments';
    }
}
