<?php

namespace App\Filament\Organizer\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $slug = 'profile';

    protected static string $view = 'filament.organizer.pages.profile';

    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->form->fill([
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('logout')
                ->label('Sign Out')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Sign Out')
                ->modalDescription('Are you sure you want to sign out?')
                ->modalSubmitActionLabel('Yes, sign out')
                ->action(function () {
                    auth()->logout();

                    return redirect('/organizer/dashboard/login');
                }),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Personal Information')
                    ->schema([
                        TextInput::make('full_name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique('users', 'email', ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->where('id', '!=', auth()->id())),
                        TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(255),
                    ]),
                Section::make('Change Password')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->currentPassword()
                            ->revealable()
                            ->autocomplete('current-password'),
                        TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->confirmed()
                            ->autocomplete('new-password'),
                        TextInput::make('password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $user = auth()->user();

        // Update basic info
        $user->update([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
        ]);

        // Update password if provided
        if (! empty($data['password'])) {
            $user->update([
                'password' => Hash::make($data['password']),
            ]);
        }

        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }
}
