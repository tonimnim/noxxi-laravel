@php
    $currentRoute = request()->route()->getName();
    $currentPath = request()->path();
    
    $tabs = [
        'profile' => [
            'label' => 'Profile',
            'icon' => 'heroicon-o-user-circle',
            'url' => '/organizer/dashboard/profile',
        ],
        'payment-method-settings' => [
            'label' => 'Payout Settings',
            'icon' => 'heroicon-o-banknotes',
            'url' => '/organizer/dashboard/payment-method-settings',
        ],
        'event-defaults' => [
            'label' => 'Event Defaults',
            'icon' => 'heroicon-o-cog',
            'url' => '/organizer/dashboard/event-defaults',
        ],
        'managers' => [
            'label' => 'Team Management',
            'icon' => 'heroicon-o-user-group',
            'url' => '/organizer/dashboard/managers',
        ],
        'notification-preferences' => [
            'label' => 'Notification Preferences',
            'icon' => 'heroicon-o-bell',
            'url' => '/organizer/dashboard/notification-preferences',
        ],
    ];
@endphp

<style>
@media (max-width: 640px) {
    .settings-nav {
        width: 4rem !important;
    }
    .settings-nav .nav-label {
        display: none !important;
    }
    .settings-nav .nav-link {
        justify-content: center !important;
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }
    .settings-heading {
        display: none !important;
    }
}
</style>

<div class="settings-nav w-60 shrink-0">
    <div class="settings-heading mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Settings</h2>
    </div>
    <nav class="space-y-1">
        @foreach($tabs as $key => $tab)
            @php
                $isActive = str_contains($currentPath, $key);
            @endphp
            <a 
                href="{{ $tab['url'] }}"
                class="nav-link group flex items-center justify-start gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200
                       {{ $isActive 
                          ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 shadow-sm' 
                          : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 hover:text-gray-900 dark:hover:text-white' 
                       }}"
            >
                <x-filament::icon 
                    :icon="$tab['icon']" 
                    class="h-5 w-5 flex-shrink-0"
                />
                <span class="nav-label truncate">{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </nav>
</div>