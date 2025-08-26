<?php

namespace App\Filament\Organizer\Pages;

use App\Models\Payout;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class PayoutDashboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Payouts';

    protected static ?int $navigationSort = 6;

    protected static bool $shouldRegisterNavigation = true; 

    protected static string $view = 'filament.organizer.pages.payout-dashboard';

    public function getHeading(): string
    {
        return ''; // Remove the heading
    }

    public function getSubheading(): ?string
    {
        return null; // Remove the subheading
    }

    public function table(Table $table): Table
    {
        $organizerId = auth()->user()?->organizer?->id;
        
        return $table
            ->query(
                Payout::query()
                    ->when($organizerId, fn($query) => $query->where('organizer_id', $organizerId))
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('requested_at')
                    ->label('Date')
                    ->dateTime('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Amount')
                    ->money('kes')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved', 'processing' => 'info',
                        'completed', 'paid' => 'success',
                        'failed' => 'danger',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime('M d, Y')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => \App\Filament\Organizer\Resources\PayoutResource::getUrl('view', ['record' => $record])),

                Tables\Actions\Action::make('receipt')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['completed', 'paid']))
                    ->url(fn ($record) => route('organizer.payout.receipt', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->poll('30s')
            ->emptyStateHeading('No Payout History')
            ->emptyStateDescription('Your payout requests will appear here')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}