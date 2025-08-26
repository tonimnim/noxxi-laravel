<?php

namespace App\Filament\Organizer\Resources;

use App\Filament\Organizer\Resources\RefundRequestResource\Pages;
use App\Filament\Organizer\Resources\RefundRequestResource\Tables\RefundRequestTable;
use App\Models\RefundRequest;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RefundRequestResource extends Resource
{
    protected static ?string $model = RefundRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationLabel = 'Refunds';

    protected static ?string $modelLabel = 'Refund Request';

    protected static ?string $pluralModelLabel = 'Refund Requests';

    protected static ?string $slug = 'refund-requests';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        // No form needed - refunds are created by customers
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return RefundRequestTable::table($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $organizerId = Auth::user()->organizer?->id;

        return parent::getEloquentQuery()
            ->whereHas('booking.event', function ($query) use ($organizerId) {
                $query->where('organizer_id', $organizerId);
            })
            ->with([
                'booking' => function ($query) {
                    $query->with(['event', 'user']);
                },
                'user',
                'transaction',
            ])
            ->latest();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRefundRequests::route('/'),
            'view' => Pages\ViewRefundRequest::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $organizerId = Auth::user()->organizer?->id;
        if (! $organizerId) {
            return null;
        }

        return cache()->remember(
            "organizer.{$organizerId}.pending_refunds_count",
            now()->addMinute(),
            fn () => static::getModel()::whereHas('booking.event', function ($query) use ($organizerId) {
                $query->where('organizer_id', $organizerId);
            })
                ->where('status', 'pending')
                ->count()
        );
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pending refund requests';
    }
}
