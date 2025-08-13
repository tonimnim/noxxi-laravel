<div wire:key="recent-activity-{{ now()->timestamp }}">
    <x-filament::section>
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Recent Activity</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Latest updates from your events</p>
            </div>
            <a href="/organizer/dashboard/activity" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                View all
            </a>
        </div>
        
        {{-- Activity Feed --}}
        <div class="space-y-4 flex-1 overflow-y-auto" style="min-height: 400px;">
            @forelse($this->getActivities() as $activity)
                <div class="flex gap-3 group">
                    {{-- Icon --}}
                    <div class="flex-shrink-0">
                        <div class="relative">
                            @php
                                $iconColors = [
                                    'success' => 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400',
                                    'info' => 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400',
                                    'warning' => 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400',
                                    'primary' => 'bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-400',
                                    'danger' => 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400',
                                ];
                                $iconColor = $iconColors[$activity['color']] ?? $iconColors['primary'];
                            @endphp
                            <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $iconColor }} ring-4 ring-white dark:ring-gray-800">
                                @switch($activity['icon'])
                                    @case('shopping-cart')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        @break
                                    @case('credit-card')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                        </svg>
                                        @break
                                    @case('sparkles')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                        </svg>
                                        @break
                                    @case('qrcode')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h2M4 12h8m-4 0v8m0-8H4v8h4z"></path>
                                        </svg>
                                        @break
                                    @default
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                @endswitch
                            </div>
                            {{-- Connecting line --}}
                            @if(!$loop->last)
                                <div class="absolute top-10 left-5 w-0.5 h-full -ml-px bg-gray-200 dark:bg-gray-700"></div>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $activity['title'] }}
                                    @if($activity['amount'])
                                        <span class="ml-1 font-semibold text-green-600 dark:text-green-400">
                                            ${{ number_format($activity['amount'], 2) }}
                                        </span>
                                    @endif
                                </p>
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $activity['description'] }}
                                </p>
                            </div>
                            <time class="flex-shrink-0 text-xs text-gray-400 dark:text-gray-500">
                                {{ $activity['time_human'] }}
                            </time>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex items-center justify-center h-full min-h-[550px]">
                    <div class="text-center">
                        <div class="mx-auto w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">No recent activity</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Activity from your events will appear here
                        </p>
                    </div>
                </div>
            @endforelse
        </div>
        
        {{-- Bottom gradient fade for scroll indication --}}
        @if(count($this->getActivities()) > 5)
            <div class="absolute bottom-0 left-0 right-0 h-8 bg-gradient-to-t from-white dark:from-gray-800 to-transparent pointer-events-none rounded-b-xl"></div>
        @endif
    </x-filament::section>
</div>