<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>System Announcements</span>
                <x-filament::icon-button
                    icon="heroicon-m-arrow-path"
                    size="sm"
                    color="gray"
                    wire:click="$refresh"
                    title="Refresh"
                />
            </div>
        </x-slot>

        @if($isLoading)
            {{-- Loading skeleton --}}
            <div class="space-y-4">
                @for($i = 0; $i < 3; $i++)
                    <div class="animate-pulse">
                        <div class="flex items-start space-x-3">
                            <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                            <div class="flex-1">
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
                                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-full mb-1"></div>
                                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-5/6"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        @elseif($hasAnnouncements)
            <div class="space-y-4">
                @foreach($announcements as $announcement)
                    <div class="border-l-4 border-{{ $announcement['color'] }}-500 bg-{{ $announcement['color'] }}-50 dark:bg-{{ $announcement['color'] }}-900/20 p-4 rounded-r-lg">
                        <div class="flex items-start">
                            <x-dynamic-component 
                                :component="'heroicon-m-' . $announcement['icon']" 
                                class="w-5 h-5 text-{{ $announcement['color'] }}-600 dark:text-{{ $announcement['color'] }}-400 mr-3 mt-0.5"
                            />
                            <div class="flex-1">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $announcement['title'] }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $announcement['message'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                                    {{ $announcement['time'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <x-heroicon-o-megaphone class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No active announcements
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    System running normally
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>