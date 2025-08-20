<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Admin\Pages\Auth\Login::class)
            ->emailVerification()
            ->brandName('NOXXI Admin')
            ->brandLogo(null)
            ->favicon(null)
            ->colors([
                'primary' => Color::Indigo,
                'danger' => Color::Red,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Green,
                'warning' => Color::Orange,
            ])
            ->darkMode(true)
            ->font('Inter')
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                \App\Filament\Admin\Pages\Dashboard::class,
                \App\Filament\Admin\Pages\System::class,
            ])
            // Register widgets used in pages
            ->widgets([
                // Dashboard widgets
                \App\Filament\Admin\Widgets\AdminStatsOverview::class,
                \App\Filament\Admin\Widgets\RevenueChart::class,
                \App\Filament\Admin\Widgets\PendingActions::class,
                \App\Filament\Admin\Widgets\LiveActivityFeed::class,
                
                // System page widgets
                \App\Filament\Admin\Widgets\System\PlatformHealthMonitor::class,
                \App\Filament\Admin\Widgets\System\SystemAnnouncements::class,
                \App\Filament\Admin\Widgets\System\GeographicHeatMapWidget::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\AdminOnly::class,
            ])
            ->authGuard('web')
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('15rem')
            ->maxContentWidth('full')
            ->spa()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearch()
            ->globalSearchDebounce('300ms')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldSuffix('⌘K')
            ->renderHook(
                'panels::user-menu.before',
                fn () => view('filament.admin.partials.header-icons')
            )
            ->renderHook(
                'panels::body.end',
                fn () => view('filament.admin.partials.quick-actions')
            );
    }
}
