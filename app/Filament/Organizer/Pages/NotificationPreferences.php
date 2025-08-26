<?php

namespace App\Filament\Organizer\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class NotificationPreferences extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $slug = 'notification-preferences';

    protected static string $view = 'filament.organizer.pages.notification-preferences';

    public ?array $data = [];

    public function mount(): void
    {
        $organizer = auth()->user()->organizer;

        $this->form->fill([
            'email_new_booking' => $organizer->email_new_booking ?? true,
            'email_payment_received' => $organizer->email_payment_received ?? true,
            'email_refund_request' => $organizer->email_refund_request ?? true,
            'email_low_tickets' => $organizer->email_low_tickets ?? true,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Email Notifications')
                    ->schema([
                        Toggle::make('email_new_booking')
                            ->label('New Booking')
                            ->helperText('Receive email when someone books tickets for your event'),

                        Toggle::make('email_payment_received')
                            ->label('Payment Received')
                            ->helperText('Receive email when payment is confirmed'),

                        Toggle::make('email_refund_request')
                            ->label('Refund Request')
                            ->helperText('Receive email when customer requests a refund'),

                        Toggle::make('email_low_tickets')
                            ->label('Low Ticket Alert')
                            ->helperText('Receive email when ticket availability is running low'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $organizer = auth()->user()->organizer;
        $organizer->update($data);

        Notification::make()
            ->title('Notification preferences updated successfully')
            ->success()
            ->send();
    }
}
