<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;

class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();
    }
    
    public function getHeading(): string|Htmlable
    {
        return '';
    }
    
    public function getTitle(): string
    {
        return 'Admin Login | NOXXI';
    }
    
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email')
            ->email()
            ->required()
            ->autocomplete('email')
            ->placeholder('admin@example.com')
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }
    
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Password')
            ->password()
            ->required()
            ->placeholder('••••••••')
            ->extraInputAttributes(['tabindex' => 2]);
    }
    
    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }
    
    public function getView(): string
    {
        return 'filament.admin.pages.auth.login';
    }
    
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
    
    public function authenticate(): ?LoginResponse
    {
        try {
            $data = $this->form->getState();
            
            // Check if user is admin before attempting login
            $user = \App\Models\User::where('email', $data['email'])->first();
            
            if (!$user || $user->role !== 'admin') {
                $this->throwFailureValidationException();
            }
            
            // Proceed with normal authentication
            return parent::authenticate();
            
        } catch (ValidationException $exception) {
            throw $exception;
        }
    }
}