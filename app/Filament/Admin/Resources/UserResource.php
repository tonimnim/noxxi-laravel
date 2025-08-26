<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 3;

    // Global search configuration
    protected static ?string $recordTitleAttribute = 'full_name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['full_name', 'email', 'phone_number'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->full_name;
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Email' => $record->email,
            'Role' => ucfirst($record->role),
            'Status' => $record->is_active ? 'Active' : 'Inactive',
        ];
    }

    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['organizer']); // Eager load relationships for performance
    }

    public static function getNavigationBadge(): ?string
    {
        return cache()->remember('admin.users.pending_verification', 60, function () {
            return User::whereNull('email_verified_at')->count() ?: null;
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
                Forms\Components\TextInput::make('full_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('role')
                    ->options([
                        'user' => 'User',
                        'organizer' => 'Organizer',
                        'admin' => 'Admin',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, $record) {
                        // Check for related records
                        $relatedCount = [
                            'bookings' => $record->bookings()->count(),
                            'transactions' => $record->transactions()->count(),
                        ];

                        $hasRelated = array_sum($relatedCount) > 0;

                        if ($hasRelated) {
                            $message = 'This user has related records that will be deleted:';
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
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, $records) {
                            $totalRelated = 0;
                            foreach ($records as $record) {
                                $totalRelated += $record->bookings()->count();
                                $totalRelated += $record->transactions()->count();
                            }

                            if ($totalRelated > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('Warning')
                                    ->body("Deleting these users will also delete {$totalRelated} related records.")
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
