<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Settings';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.admin.pages.settings';
    
    public ?array $emailTemplates = [];
    
    public function mount(): void
    {
        // Load email templates from cache
        $this->emailTemplates = [
            'payout_approved' => cache()->get('email_template_payout_approved', $this->getDefaultTemplate('payout_approved')),
            'payout_on_hold' => cache()->get('email_template_payout_on_hold', $this->getDefaultTemplate('payout_on_hold')),
        ];
        
        $this->form->fill(['emailTemplates' => $this->emailTemplates]);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Settings')
                    ->tabs([
                        Tab::make('Email Templates')
                            ->schema([
                                Section::make('Payout Approved Email')
                                    ->description('Email sent when a payout request is approved')
                                    ->schema([
                                        TextInput::make('emailTemplates.payout_approved.subject')
                                            ->label('Subject')
                                            ->required()
                                            ->helperText('Available variables: {organizer_name}, {currency}, {amount}, {reference}, {payment_method}'),
                                        TextInput::make('emailTemplates.payout_approved.greeting')
                                            ->label('Greeting')
                                            ->required(),
                                        Textarea::make('emailTemplates.payout_approved.line1')
                                            ->label('Main Message')
                                            ->rows(2)
                                            ->required(),
                                        Textarea::make('emailTemplates.payout_approved.line2')
                                            ->label('Reference Line')
                                            ->rows(1),
                                        Textarea::make('emailTemplates.payout_approved.line3')
                                            ->label('Payment Information')
                                            ->rows(2)
                                            ->helperText('Use {payment_method} to include payment method'),
                                        Textarea::make('emailTemplates.payout_approved.footer')
                                            ->label('Footer')
                                            ->rows(2),
                                    ]),
                                    
                                Section::make('Payout On Hold Email')
                                    ->description('Email sent when a payout request is placed on hold')
                                    ->schema([
                                        TextInput::make('emailTemplates.payout_on_hold.subject')
                                            ->label('Subject')
                                            ->required()
                                            ->helperText('Available variables: {organizer_name}, {currency}, {amount}, {reference}, {reason}'),
                                        TextInput::make('emailTemplates.payout_on_hold.greeting')
                                            ->label('Greeting')
                                            ->required(),
                                        Textarea::make('emailTemplates.payout_on_hold.line1')
                                            ->label('Main Message')
                                            ->rows(2)
                                            ->required(),
                                        Textarea::make('emailTemplates.payout_on_hold.line2')
                                            ->label('Reference Line')
                                            ->rows(1),
                                        Textarea::make('emailTemplates.payout_on_hold.line3')
                                            ->label('Reason Line')
                                            ->rows(1)
                                            ->helperText('Use {reason} to include the hold reason'),
                                        Textarea::make('emailTemplates.payout_on_hold.line4')
                                            ->label('Support Information')
                                            ->rows(2),
                                        Textarea::make('emailTemplates.payout_on_hold.footer')
                                            ->label('Footer')
                                            ->rows(2),
                                    ]),
                            ]),
                            
                        Tab::make('Platform Settings')
                            ->schema([
                                Section::make('Coming Soon')
                                    ->description('Platform configuration settings will be available here'),
                            ]),
                            
                        Tab::make('Payment Gateways')
                            ->schema([
                                Section::make('Coming Soon')
                                    ->description('Payment gateway configurations will be available here'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }
    
    public function save(): void
    {
        $data = $this->form->getState();
        
        // Save email templates to cache
        foreach ($data['emailTemplates'] as $key => $template) {
            cache()->forever('email_template_' . $key, $template);
        }
        
        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
    
    protected function getDefaultTemplate(string $type): array
    {
        $templates = [
            'payout_approved' => [
                'subject' => 'Payout Request Approved',
                'greeting' => 'Hello {organizer_name},',
                'line1' => 'Great news! Your payout request of {currency} {amount} has been approved and is being processed.',
                'line2' => 'Reference: {reference}',
                'line3' => 'The funds will be transferred to your registered {payment_method} within 24-48 hours.',
                'footer' => 'Thank you for using our platform!',
            ],
            'payout_on_hold' => [
                'subject' => 'Payout Request On Hold',
                'greeting' => 'Hello {organizer_name},',
                'line1' => 'Your payout request of {currency} {amount} has been placed on hold for review.',
                'line2' => 'Reference: {reference}',
                'line3' => 'Reason: {reason}',
                'line4' => 'Our team will review your request and get back to you within 2-3 business days. If you have any questions, please don\'t hesitate to contact our support team.',
                'footer' => 'Thank you for your patience and understanding.',
            ],
        ];
        
        return $templates[$type] ?? [];
    }
    
    public function getHeading(): string
    {
        return 'Platform Settings';
    }
    
    public function getSubheading(): ?string
    {
        return 'Configure email templates, platform settings, and payment gateways';
    }
}