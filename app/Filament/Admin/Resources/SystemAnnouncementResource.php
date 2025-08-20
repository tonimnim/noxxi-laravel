<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SystemAnnouncementResource\Pages;
use App\Filament\Admin\Resources\SystemAnnouncementResource\RelationManagers;
use App\Models\SystemAnnouncement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;

class SystemAnnouncementResource extends Resource
{
    protected static ?string $model = SystemAnnouncement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 10;
    
    protected static ?string $navigationLabel = 'Announcements';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options([
                                'maintenance' => 'Maintenance',
                                'update' => 'Update',
                                'alert' => 'Alert',
                                'info' => 'Information',
                            ])
                            ->required()
                            ->default('info')
                            ->native(false),
                            
                        Forms\Components\Select::make('priority')
                            ->label('Priority')
                            ->options([
                                'critical' => 'Critical',
                                'high' => 'High',
                                'medium' => 'Medium',
                                'low' => 'Low',
                            ])
                            ->required()
                            ->default('low')
                            ->native(false),
                            
                        Forms\Components\TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan('full'),
                            
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->rows(4)
                            ->columnSpan('full'),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Active announcements will be displayed to users'),
                            
                        Forms\Components\DateTimePicker::make('scheduled_for')
                            ->label('Schedule For')
                            ->helperText('Leave empty to display immediately')
                            ->native(false),
                            
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->helperText('Leave empty for no expiration')
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'maintenance' => 'warning',
                        'update' => 'info',
                        'alert' => 'danger',
                        'info' => 'gray',
                        default => 'gray',
                    })
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(50),
                    
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                    
                Tables\Columns\TextColumn::make('scheduled_for')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->label('Scheduled'),
                    
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->label('Expires'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->label('Created'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'maintenance' => 'Maintenance',
                        'update' => 'Update',
                        'alert' => 'Alert',
                        'info' => 'Information',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'critical' => 'Critical',
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListSystemAnnouncements::route('/'),
            'create' => Pages\CreateSystemAnnouncement::route('/create'),
            'edit' => Pages\EditSystemAnnouncement::route('/{record}/edit'),
        ];
    }
}
