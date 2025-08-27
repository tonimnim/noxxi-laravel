<?php

namespace App\Filament\Organizer\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class EventDefaults extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static ?string $slug = 'event-defaults';

    protected static string $view = 'filament.organizer.pages.event-defaults';

    public ?array $data = [];

    public function mount(): void
    {
        $organizer = auth()->user()->organizer;

        $this->form->fill([
            'default_currency' => $organizer->default_currency ?? 'KES',
            'default_terms' => $organizer->default_terms ?? '',
            'default_refund_policy' => $organizer->default_refund_policy ?? '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Default Settings')
                    ->schema([
                        Select::make('default_currency')
                            ->label('Default Currency')
                            ->options([
                                'KES' => 'KES - Kenyan Shilling',
                                'NGN' => 'NGN - Nigerian Naira',
                                'ZAR' => 'ZAR - South African Rand',
                                'GHS' => 'GHS - Ghanaian Cedi',
                                'UGX' => 'UGX - Ugandan Shilling',
                                'TZS' => 'TZS - Tanzanian Shilling',
                                'EGP' => 'EGP - Egyptian Pound',
                                'USD' => 'USD - US Dollar',
                            ])
                            ->required(),

                        Textarea::make('default_terms')
                            ->label('Default Terms & Conditions')
                            ->helperText('This will be pre-filled when creating new events')
                            ->rows(6)
                            ->maxLength(5000),

                        Textarea::make('default_refund_policy')
                            ->label('Default Refund Policy')
                            ->helperText('This will be pre-filled when creating new events')
                            ->rows(6)
                            ->maxLength(5000),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $organizer = auth()->user()->organizer;
        $organizer->update([
            'default_currency' => $data['default_currency'],
            'default_terms' => $data['default_terms'],
            'default_refund_policy' => $data['default_refund_policy'],
        ]);

        // Clear the balance cache when currency is updated so it reflects immediately
        \Illuminate\Support\Facades\Cache::forget("organizer_balance_{$organizer->id}");

        Notification::make()
            ->title('Event defaults updated successfully')
            ->success()
            ->send();
    }
}
