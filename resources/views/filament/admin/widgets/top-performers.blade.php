<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Top Performers
        </x-slot>
        
        <div x-data="{ activeTab: @entangle('activeTab') }">
            {{-- Tabs --}}
            <div class="flex space-x-1 border-b border-gray-200 dark:border-gray-700 mb-4">
                <button
                    @click="activeTab = 'organizers'"
                    :class="activeTab === 'organizers' 
                        ? 'border-primary-500 text-primary-600 dark:text-primary-400' 
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="px-4 py-2 font-medium text-sm border-b-2 transition-colors"
                >
                    Organizers
                </button>
                <button
                    @click="activeTab = 'events'"
                    :class="activeTab === 'events' 
                        ? 'border-primary-500 text-primary-600 dark:text-primary-400' 
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="px-4 py-2 font-medium text-sm border-b-2 transition-colors"
                >
                    Events
                </button>
            </div>
            
            {{-- Organizers Tab Content --}}
            <div x-show="activeTab === 'organizers'" x-transition>
                <div class="space-y-3">
                    @forelse($this->getTopOrganizers() as $organizer)
                        <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <div class="flex items-center gap-3">
                                {{-- Rank Badge --}}
                                <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full 
                                    {{ $organizer['rank'] === 1 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400' : 
                                       ($organizer['rank'] === 2 ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' : 
                                       ($organizer['rank'] === 3 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400' : 
                                       'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400')) }}">
                                    <span class="text-sm font-bold">{{ $organizer['rank'] }}</span>
                                </div>
                                
                                {{-- Organizer Info --}}
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $organizer['name'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $organizer['metric'] }} • {{ $organizer['bookings'] }} bookings
                                    </p>
                                </div>
                            </div>
                            
                            {{-- Revenue --}}
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $organizer['revenue_formatted'] }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                No data available
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
            
            {{-- Events Tab Content --}}
            <div x-show="activeTab === 'events'" x-transition>
                <div class="space-y-3">
                    @forelse($this->getTopEvents() as $event)
                        <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <div class="flex items-center gap-3">
                                {{-- Rank Badge --}}
                                <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full 
                                    {{ $event['rank'] === 1 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400' : 
                                       ($event['rank'] === 2 ? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' : 
                                       ($event['rank'] === 3 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400' : 
                                       'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400')) }}">
                                    <span class="text-sm font-bold">{{ $event['rank'] }}</span>
                                </div>
                                
                                {{-- Event Info --}}
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $event['name'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $event['date'] }} • {{ $event['metric'] }}
                                    </p>
                                </div>
                            </div>
                            
                            {{-- Tickets --}}
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $event['tickets_formatted'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $event['revenue_formatted'] }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                No data available
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        
        {{-- View All Link --}}
        <div class="pt-3 mt-3 border-t border-gray-200 dark:border-gray-700">
            <a href="#" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium">
                View full leaderboard →
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>