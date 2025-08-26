<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\OrganizerResource\Pages;
use App\Models\Organizer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrganizerResource extends Resource
{
    protected static ?string $model = Organizer::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Organizers';

    protected static ?int $navigationSort = 2;

    // Global search configuration
    protected static ?string $recordTitleAttribute = 'business_name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['business_name', 'business_type', 'business_country', 'business_address'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->business_name;
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Type' => ucfirst(str_replace('_', ' ', $record->business_type)),
            'Country' => $record->business_country ?? 'Not specified',
            'Verified' => $record->is_verified ? 'Yes' : 'No',
            'Active' => $record->is_active ? 'Yes' : 'No',
        ];
    }

    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->select(['id', 'business_name', 'business_type', 'business_country', 'is_verified', 'is_active', 'user_id'])
            ->with(['user:id,email,full_name']); // Optimized eager loading
    }

    public static function getNavigationBadge(): ?string
    {
        return cache()->remember('admin.organizers.pending_approval', 60, function () {
            return Organizer::where('is_verified', false)->count() ?: null;
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Business Information')
                    ->schema([
                        Forms\Components\TextInput::make('business_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('business_type')
                            ->options([
                                'individual' => 'Individual',
                                'company' => 'Company',
                                'non_profit' => 'Non Profit',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('business_country')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('business_address')
                            ->maxLength(500),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Verification')
                    ->schema([
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Verified')
                            ->inline(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->inline(false),
                        Forms\Components\Select::make('status')
                            ->label('Organizer Status')
                            ->options([
                                'normal' => 'Normal Organizer',
                                'premium' => 'Premium Organizer',
                            ])
                            ->default('normal')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'premium') {
                                    $set('commission_rate', 1.5);
                                    $set('absorb_payout_fees', true);
                                    $set('auto_featured_listings', true);
                                } else {
                                    $set('commission_rate', 8);
                                    $set('absorb_payout_fees', false);
                                    $set('auto_featured_listings', false);
                                }
                            })
                            ->helperText('Premium organizers get 1.5% commission rate and additional perks'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Commission & Finance')
                    ->schema([
                        Forms\Components\TextInput::make('commission_rate')
                            ->numeric()
                            ->suffix('%')
                            ->required()
                            ->default(8)
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.1)
                            ->helperText(fn ($get) => $get('status') === 'premium'
                                ? 'Premium rate: 1.5% (default)'
                                : 'Normal rate: 8% (default)'),
                        // Note: Removed total_revenue, total_commission_paid, and default_currency fields
                        // These are calculated values that should be viewed in reports, not edited here
                        Forms\Components\Placeholder::make('revenue_info')
                            ->label('Financial Summary')
                            ->content(function ($record) {
                                if (! $record) {
                                    return 'No financial data available yet';
                                }

                                // Calculate actual revenue from transactions
                                $totalRevenue = $record->transactions()
                                    ->where('type', 'ticket_sale')
                                    ->where('status', 'completed')
                                    ->sum('amount');

                                $totalCommission = $record->transactions()
                                    ->where('type', 'ticket_sale')
                                    ->where('status', 'completed')
                                    ->sum('commission_amount');

                                $currency = $record->default_currency ?? 'KES';

                                return new \Illuminate\Support\HtmlString(
                                    "<div class='space-y-2'>
                                        <div><strong>Total Revenue:</strong> {$currency} ".number_format($totalRevenue, 2)."</div>
                                        <div><strong>Total Commission:</strong> {$currency} ".number_format(abs($totalCommission), 2)."</div>
                                        <div><strong>Default Currency:</strong> {$currency}</div>
                                    </div>"
                                );
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Premium Perks')
                    ->schema([
                        Forms\Components\Toggle::make('absorb_payout_fees')
                            ->label('Platform Absorbs Payout Fees')
                            ->helperText('When enabled, platform pays the payout fees')
                            ->inline(false),
                        Forms\Components\Toggle::make('auto_featured_listings')
                            ->label('Auto-Featured Listings')
                            ->helperText('Listings automatically featured on homepage')
                            ->inline(false),
                    ])
                    ->columns(2)
                    ->visible(fn ($get) => $get('status') === 'premium'),

                Forms\Components\Section::make('Banking Information')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('bank_account_number')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('bank_account_name')
                            ->maxLength(255),
                        Forms\Components\Select::make('payout_frequency')
                            ->options([
                                'weekly' => 'Weekly',
                                'bi_weekly' => 'Bi-Weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->default('weekly'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'premium' => 'success',
                        'normal' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('business_type')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Verified')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->suffix('%')
                    ->color(fn ($state): string => $state <= 1.5 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('total_revenue')
                    ->money('kes')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_commission_paid')
                    ->label('Commission Paid')
                    ->money('kes')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'normal' => 'Normal',
                        'premium' => 'Premium',
                    ]),
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Verified'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('togglePremium')
                    ->label(fn ($record) => $record->status === 'premium' ? 'Downgrade to Normal' : 'Upgrade to Premium')
                    ->icon(fn ($record) => $record->status === 'premium' ? 'heroicon-o-arrow-down-circle' : 'heroicon-o-arrow-up-circle')
                    ->color(fn ($record) => $record->status === 'premium' ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->status === 'premium' ? 'Downgrade to Normal Organizer' : 'Upgrade to Premium Organizer')
                    ->modalDescription(fn ($record) => $record->status === 'premium'
                        ? 'This will change commission rate to 8% and remove premium perks.'
                        : 'This will change commission rate to 1.5% and enable premium perks.')
                    ->action(function ($record) {
                        if ($record->status === 'premium') {
                            $record->update([
                                'status' => 'normal',
                                'commission_rate' => 8,
                                'absorb_payout_fees' => false,
                                'auto_featured_listings' => false,
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Organizer downgraded to Normal')
                                ->success()
                                ->send();
                        } else {
                            $record->update([
                                'status' => 'premium',
                                'commission_rate' => 1.5,
                                'absorb_payout_fees' => true,
                                'auto_featured_listings' => true,
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title('Organizer upgraded to Premium')
                                ->success()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, $record) {
                        // Check for related records
                        $relatedCount = [
                            'events' => $record->events()->count(),
                            'transactions' => $record->transactions()->count(),
                            'payouts' => $record->payouts()->count(),
                        ];

                        $hasRelated = array_sum($relatedCount) > 0;

                        if ($hasRelated) {
                            $message = 'This organizer has related records that will be deleted:';
                            foreach ($relatedCount as $type => $count) {
                                if ($count > 0) {
                                    $message .= "\n- {$count} {$type}";
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Related Records Will Be Deleted')
                                ->body($message)
                                ->persistent()
                                ->send();
                        }

                        // Also delete the associated user account
                        if ($record->user) {
                            $record->user->delete();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, $records) {
                            $totalRelated = 0;
                            foreach ($records as $record) {
                                $totalRelated += $record->events()->count();
                                $totalRelated += $record->transactions()->count();
                                $totalRelated += $record->payouts()->count();
                            }

                            if ($totalRelated > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('Warning')
                                    ->body("Deleting these organizers will also delete {$totalRelated} related records and their user accounts.")
                                    ->persistent()
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizers::route('/'),
            'create' => Pages\CreateOrganizer::route('/create'),
            'edit' => Pages\EditOrganizer::route('/{record}/edit'),
        ];
    }
}
