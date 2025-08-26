<?php

namespace App\Providers\Filament;

use App\Http\Middleware\RedirectIfNotOrganizer;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class OrganizerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('organizer')
            ->path('organizer/dashboard')
            ->login()
            ->emailVerification()
            ->brandName('NOXXI Organizer')
            ->brandLogo(null)
            ->favicon(null)
            ->colors([
                'primary' => Color::Gray,
                'danger' => Color::Red,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Green,
                'warning' => Color::Orange,
            ])
            ->darkMode(true)
            ->font('Inter')
            ->discoverResources(in: app_path('Filament/Organizer/Resources'), for: 'App\\Filament\\Organizer\\Resources')
            ->discoverPages(in: app_path('Filament/Organizer/Pages'), for: 'App\\Filament\\Organizer\\Pages')
            ->pages([
                \App\Filament\Organizer\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Organizer/Widgets'), for: 'App\\Filament\\Organizer\\Widgets')
            ->widgets([
                // Additional widgets can be registered here
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                RedirectIfNotOrganizer::class,
            ])
            ->authGuard('web')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('15rem') // Reduce sidebar width (default is 20rem)
            ->maxContentWidth('full')
            ->spa()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearchDebounce('500ms')
            ->renderHook(
                'panels::user-menu.before',
                fn () => view('filament.organizer.partials.header-icons')
            )
            ->userMenuItems([
                'profile' => \Filament\Navigation\MenuItem::make()
                    ->label('Profile Settings')
                    ->url('/organizer/dashboard/profile')
                    ->icon('heroicon-o-user-circle'),
            ]);
    }
}
