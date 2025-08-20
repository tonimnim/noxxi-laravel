<?php

namespace App\Filament\Organizer\Resources\BookingResource\Pages;

use App\Filament\Organizer\Resources\BookingResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;

class ViewBooking extends ViewRecord
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
            Actions\Action::make('download_tickets')
                ->label('Download Tickets')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->visible(fn (): bool => $this->record->payment_status === 'paid' &&
                    $this->record->status === 'confirmed'
                ),

            Actions\Action::make('resend_email')
                ->label('Resend Email')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->payment_status === 'paid'),

            Actions\Action::make('refund')
                ->label('Refund')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Refund Booking')
                ->form([
                    \Filament\Forms\Components\Textarea::make('reason')
                        ->label('Refund Reason')
                        ->required()
                        ->maxLength(500),
                ])
                ->visible(fn (): bool => $this->record->payment_status === 'paid' &&
                    ! in_array($this->record->status, ['refunded', 'cancelled'])
                ),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Group::make([
                    Infolists\Components\Section::make('Booking Information')
                        ->icon('heroicon-o-ticket')
                        ->schema([
                            Infolists\Components\Grid::make(3)
                                ->schema([
                                    Infolists\Components\TextEntry::make('booking_reference')
                                        ->label('Reference')
                                        ->copyable()
                                        ->copyMessage('Reference copied')
                                        ->weight(FontWeight::Bold)
                                        ->size('lg'),

                                    Infolists\Components\TextEntry::make('status')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'confirmed' => 'success',
                                            'pending' => 'warning',
                                            'cancelled' => 'danger',
                                            'expired' => 'gray',
                                            'refunded' => 'info',
                                            default => 'gray',
                                        }),

                                    Infolists\Components\TextEntry::make('payment_status')
                                        ->label('Payment')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'paid' => 'success',
                                            'processing' => 'warning',
                                            'unpaid' => 'gray',
                                            'failed' => 'danger',
                                            'refunded', 'partial_refund' => 'info',
                                            default => 'gray',
                                        }),
                                ]),

                            Infolists\Components\Grid::make(2)
                                ->schema([
                                    Infolists\Components\TextEntry::make('created_at')
                                        ->label('Booking Date')
                                        ->dateTime('M d, Y g:i A'),

                                    Infolists\Components\TextEntry::make('booking_source')
                                        ->label('Source')
                                        ->badge()
                                        ->color('gray'),
                                ]),
                        ]),

                    Infolists\Components\Section::make('Customer Information')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Infolists\Components\Grid::make(2)
                                ->schema([
                                    Infolists\Components\TextEntry::make('customer_name')
                                        ->label('Name')
                                        ->icon('heroicon-m-user'),

                                    Infolists\Components\TextEntry::make('customer_email')
                                        ->label('Email')
                                        ->icon('heroicon-m-envelope')
                                        ->copyable(),

                                    Infolists\Components\TextEntry::make('customer_phone')
                                        ->label('Phone')
                                        ->icon('heroicon-m-phone')
                                        ->copyable(),

                                    Infolists\Components\TextEntry::make('user.full_name')
                                        ->label('Account')
                                        ->placeholder('Guest checkout')
                                        ->icon('heroicon-m-user-circle'),
                                ]),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Infolists\Components\Group::make([
                    Infolists\Components\Section::make('Event Details')
                        ->icon('heroicon-o-calendar')
                        ->schema([
                            Infolists\Components\TextEntry::make('event.title')
                                ->label('Listing')
                                ->weight(FontWeight::Bold),

                            Infolists\Components\TextEntry::make('event.venue_name')
                                ->label('Venue')
                                ->icon('heroicon-m-map-pin'),

                            Infolists\Components\TextEntry::make('event.event_date')
                                ->label('Date & Time')
                                ->dateTime('M d, Y g:i A')
                                ->icon('heroicon-m-calendar'),

                            Infolists\Components\TextEntry::make('event.city')
                                ->label('Location')
                                ->formatStateUsing(fn ($record): string => $record->event->city.', '.$record->event->country
                                )
                                ->icon('heroicon-m-globe-alt'),
                        ]),

                    Infolists\Components\Section::make('Payment Details')
                        ->icon('heroicon-o-credit-card')
                        ->schema([
                            Infolists\Components\TextEntry::make('subtotal')
                                ->formatStateUsing(fn ($record): string => $record->currency.' '.number_format($record->subtotal, 2)
                                ),

                            Infolists\Components\TextEntry::make('service_fee')
                                ->formatStateUsing(fn ($record): string => $record->currency.' '.number_format($record->service_fee, 2)
                                ),

                            Infolists\Components\TextEntry::make('discount_amount')
                                ->formatStateUsing(fn ($record): string => $record->currency.' '.number_format($record->discount_amount, 2)
                                )
                                ->visible(fn ($record): bool => $record->discount_amount > 0),

                            Infolists\Components\TextEntry::make('total_amount')
                                ->label('Total')
                                ->formatStateUsing(fn ($record): string => $record->currency.' '.number_format($record->total_amount, 2)
                                )
                                ->weight(FontWeight::Bold)
                                ->size('lg'),

                            Infolists\Components\Grid::make(2)
                                ->schema([
                                    Infolists\Components\TextEntry::make('payment_method')
                                        ->label('Method')
                                        ->badge(),

                                    Infolists\Components\TextEntry::make('payment_reference')
                                        ->label('Reference')
                                        ->copyable()
                                        ->placeholder('N/A'),
                                ]),
                        ]),
                ])->columnSpan(['lg' => 1]),

                Infolists\Components\Section::make('Tickets')
                    ->icon('heroicon-o-ticket')
                    ->schema([
                        Infolists\Components\TextEntry::make('quantity')
                            ->label('Total Tickets')
                            ->formatStateUsing(fn ($state): string => $state.' '.str('ticket')->plural($state)
                            ),

                        Infolists\Components\TextEntry::make('ticket_types')
                            ->label('Ticket Breakdown')
                            ->formatStateUsing(function ($state): HtmlString {
                                if (! $state) {
                                    return new HtmlString('—');
                                }

                                $html = '<div class="space-y-2">';
                                foreach ($state as $type) {
                                    $html .= sprintf(
                                        '<div class="flex justify-between p-2 bg-gray-50 rounded">
                                            <span class="font-medium">%s</span>
                                            <span>%d × %s %s</span>
                                        </div>',
                                        $type['name'] ?? 'Unknown',
                                        $type['quantity'] ?? 0,
                                        $this->record->currency,
                                        number_format($type['price'] ?? 0, 0)
                                    );
                                }
                                $html .= '</div>';

                                return new HtmlString($html);
                            })
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('tickets_checked_in')
                            ->label('Check-in Status')
                            ->getStateUsing(fn (): string => $this->record->tickets()->where('checked_in', true)->count().
                                ' of '.$this->record->quantity.' checked in'
                            )
                            ->badge()
                            ->color(fn (): string => $this->record->hasCheckedInTickets() ? 'success' : 'gray'
                            ),
                    ])
                    ->columnSpanFull(),

                Infolists\Components\Section::make('Additional Information')
                    ->icon('heroicon-o-information-circle')
                    ->collapsed()
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('promo_code')
                                    ->label('Promo Code')
                                    ->placeholder('No promo code used'),

                                Infolists\Components\TextEntry::make('ip_address')
                                    ->label('IP Address'),

                                Infolists\Components\TextEntry::make('expires_at')
                                    ->label('Expires')
                                    ->dateTime()
                                    ->placeholder('No expiry'),
                            ]),

                        Infolists\Components\TextEntry::make('user_agent')
                            ->label('User Agent')
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state): string => $state ? substr($state, 0, 100).'...' : '—'
                            ),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }
}
