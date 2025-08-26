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

<div class="w-64 shrink-0">
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Settings</h2>
    </div>
    <nav class="space-y-2">
        @foreach($tabs as $key => $tab)
            @php
                $isActive = str_contains($currentPath, $key);
            @endphp
            <a 
                href="{{ $tab['url'] }}"
                class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors
                       {{ $isActive 
                          ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400' 
                          : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white' 
                       }}"
            >
                <x-filament::icon 
                    :icon="$tab['icon']" 
                    class="h-5 w-5"
                />
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </nav>
</div>