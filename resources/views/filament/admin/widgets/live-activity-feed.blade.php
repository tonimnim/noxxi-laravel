<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>Live Activity Feed</span>
                <div class="flex items-center gap-1">
                    <div class="h-2 w-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Live</span>
                </div>
            </div>
        </x-slot>
        
        <div class="space-y-3 max-h-96 overflow-y-auto">
            @forelse($this->getActivities() as $activity)
                <div class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer">
                    {{-- Icon --}}
                    <div class="flex-shrink-0">
                        <div class="p-2 rounded-full bg-{{ $activity['color'] }}-100 dark:bg-{{ $activity['color'] }}-900/20">
                            <x-filament::icon 
                                :icon="$activity['icon']"
                                class="h-4 w-4 text-{{ $activity['color'] }}-600 dark:text-{{ $activity['color'] }}-400"
                            />
                        </div>
                    </div>
                    
                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $activity['title'] }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                            {{ $activity['description'] }}
                        </p>
                    </div>
                    
                    {{-- Time --}}
                    <div class="flex-shrink-0">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $activity['time_human'] }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-6">
                    <x-filament::icon 
                        icon="heroicon-o-inbox"
                        class="mx-auto h-8 w-8 text-gray-400"
                    />
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        No recent activity
                    </p>
                </div>
            @endforelse
        </div>
        
        {{-- View All Link --}}
        @if($this->getActivities()->count() > 0)
            <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                <a href="#" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium">
                    View all activity →
                </a>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>