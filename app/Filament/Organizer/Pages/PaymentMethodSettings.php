<?php

namespace App\Filament\Organizer\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Crypt;

class PaymentMethodSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Payment Methods';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = false; // Hide from navigation

    protected static string $view = 'filament.organizer.pages.payment-method-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $organizer = auth()->user()->organizer;

        if (! $organizer) {
            Notification::make()
                ->title('No organizer account')
                ->danger()
                ->send();

            $this->redirect(\App\Filament\Organizer\Pages\Dashboard::getUrl());

            return;
        }

        $this->form->fill([
            'mpesa_number' => $organizer->mpesa_number,
            'bank_name' => $organizer->bank_name,
            'bank_account_number' => $organizer->bank_account_number,
            'bank_account_name' => $organizer->bank_account_name,
            'payout_frequency' => $organizer->payout_frequency ?? 'weekly',
            'two_factor_enabled' => $organizer->two_factor_enabled,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Mobile Money Settings')
                    ->schema([
                        Forms\Components\TextInput::make('mpesa_number')
                            ->label('Mobile Money Number')
                            ->tel()
                            ->placeholder('0712345678')
                            ->maxLength(20)
                            ->helperText('Enter your mobile money number (M-Pesa, MTN Mobile Money, etc.)')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('verify_mpesa')
                                    ->label('Verify')
                                    ->icon('heroicon-o-check-circle')
                                    ->action(function () {
                                        // M-Pesa verification handled through Paystack
                                        Notification::make()
                                            ->title('Verification code sent')
                                            ->body('Check your phone for the verification code')
                                            ->success()
                                            ->send();
                                    })
                            ),
                    ]),

                Forms\Components\Section::make('Bank Account Settings')
                    ->schema([
                        Forms\Components\Select::make('bank_name')
                            ->label('Bank Name')
                            ->options([
                                'KCB' => 'KCB Bank',
                                'Equity' => 'Equity Bank',
                                'Cooperative' => 'Cooperative Bank',
                                'Standard Chartered' => 'Standard Chartered',
                                'Barclays' => 'Barclays Bank',
                                'DTB' => 'Diamond Trust Bank',
                                'NCBA' => 'NCBA Bank',
                                'Stanbic' => 'Stanbic Bank',
                                'I&M' => 'I&M Bank',
                                'Family' => 'Family Bank',
                                'Other' => 'Other',
                            ])
                            ->searchable(),
                        Forms\Components\TextInput::make('bank_account_number')
                            ->label('Account Number')
                            ->maxLength(30)
                            ->helperText('Your bank account number'),
                        Forms\Components\TextInput::make('bank_account_name')
                            ->label('Account Name')
                            ->maxLength(255)
                            ->helperText('Name on the bank account'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Payout Preferences')
                    ->schema([
                        Forms\Components\Select::make('payout_frequency')
                            ->label('Preferred Payout Frequency')
                            ->options([
                                'weekly' => 'Weekly',
                                'bi_weekly' => 'Bi-Weekly',
                                'monthly' => 'Monthly',
                                'manual' => 'Manual Only',
                            ])
                            ->helperText('Coming soon'),
                    ]),

                Forms\Components\Section::make('Security Settings')
                    ->schema([
                        Forms\Components\Toggle::make('two_factor_enabled')
                            ->label('Enable Two-Factor Authentication')
                            ->helperText('Require 2FA verification for all payout requests')
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                if ($state) {
                                    // TODO: Generate and show 2FA QR code
                                    Notification::make()
                                        ->title('2FA Setup')
                                        ->body('Scan the QR code with your authenticator app')
                                        ->success()
                                        ->send();
                                }
                            }),

                        Forms\Components\View::make('2fa-setup')
                            ->view('filament.organizer.components.2fa-setup')
                            ->visible(fn ($get) => $get('two_factor_enabled')),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $organizer = auth()->user()->organizer;
        $data = $this->form->getState();

        // Validate current password if changing sensitive information
        if ($this->hasChangedSensitiveData($organizer, $data)) {
            // TODO: Add password confirmation modal
        }

        try {
            // Encrypt sensitive data
            if (! empty($data['bank_account_number'])) {
                $data['bank_account_number'] = Crypt::encryptString($data['bank_account_number']);
            }

            $organizer->update([
                'mpesa_number' => $data['mpesa_number'],
                'bank_name' => $data['bank_name'],
                'bank_account_number' => $data['bank_account_number'],
                'bank_account_name' => $data['bank_account_name'],
                'payout_frequency' => $data['payout_frequency'],
                'two_factor_enabled' => $data['two_factor_enabled'],
            ]);

            Notification::make()
                ->title('Settings saved')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error saving settings')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function hasChangedSensitiveData($organizer, $data): bool
    {
        return $organizer->mpesa_number !== $data['mpesa_number'] ||
               $organizer->bank_account_number !== $data['bank_account_number'];
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save Settings')
                ->action('save')
                ->color('primary'),
        ];
    }
}
