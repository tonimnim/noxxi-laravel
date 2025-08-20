<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span>Platform Health Monitor</span>
                    @if(!$isLoading && isset($hasCritical) && $hasCritical)
                        <span class="inline-flex items-center px-2 py-1 text-xs font-bold text-red-100 bg-red-600 rounded-full">
                            CRITICAL
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if(!$isLoading && isset($lastUpdate))
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Updated {{ $lastUpdate->diffForHumans() }}
                        </span>
                    @endif
                    <x-filament::icon-button
                        icon="heroicon-m-arrow-path"
                        size="sm"
                        color="gray"
                        wire:click="$refresh"
                        title="Refresh Metrics"
                    />
                </div>
            </div>
        </x-slot>

        @if($isLoading)
            {{-- Loading skeleton --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @for($i = 0; $i < 8; $i++)
                    <div class="relative overflow-hidden rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                        <div class="animate-pulse">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
                            <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                        </div>
                    </div>
                @endfor
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @forelse($metrics as $key => $metric)
                    @if($metric && isset($metric['label']) && isset($metric['value']))
                        <div class="relative overflow-hidden rounded-lg {{ isset($metric['status']) && $metric['status'] === 'critical' ? 'ring-2 ring-red-500' : '' }} bg-white dark:bg-gray-800 p-4 transition-all hover:shadow-md">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $metric['label'] }}
                                    </p>
                                    <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $metric['value'] }}
                                    </p>
                                    @if(isset($metric['percent']))
                                        <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                            <div class="h-1.5 rounded-full {{ $metric['status'] === 'healthy' ? 'bg-green-600' : ($metric['status'] === 'warning' ? 'bg-yellow-600' : 'bg-red-600') }}" 
                                                 style="width: {{ min($metric['percent'], 100) }}%"></div>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-2">
                                    @php
                                        $status = $metric['status'] ?? 'unknown';
                                        $statusConfig = [
                                            'healthy' => ['bg' => 'bg-green-100 dark:bg-green-900/50', 'icon' => 'heroicon-m-check-circle', 'color' => 'text-green-600 dark:text-green-400'],
                                            'info' => ['bg' => 'bg-blue-100 dark:bg-blue-900/50', 'icon' => 'heroicon-m-information-circle', 'color' => 'text-blue-600 dark:text-blue-400'],
                                            'warning' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/50', 'icon' => 'heroicon-m-exclamation-triangle', 'color' => 'text-yellow-600 dark:text-yellow-400'],
                                            'critical' => ['bg' => 'bg-red-100 dark:bg-red-900/50', 'icon' => 'heroicon-m-x-circle', 'color' => 'text-red-600 dark:text-red-400'],
                                            'unknown' => ['bg' => 'bg-gray-100 dark:bg-gray-900/50', 'icon' => 'heroicon-m-question-mark-circle', 'color' => 'text-gray-600 dark:text-gray-400'],
                                        ];
                                        $config = $statusConfig[$status] ?? $statusConfig['unknown'];
                                    @endphp
                                    <div class="p-2 rounded-full {{ $config['bg'] }}">
                                        <x-dynamic-component :component="$config['icon']" class="w-5 h-5 {{ $config['color'] }}" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="col-span-full text-center py-8">
                        <x-heroicon-o-server class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            No metrics available
                        </p>
                    </div>
                @endforelse
            </div>
            
            @if(isset($hasCritical) && $hasCritical)
                <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <div class="flex items-center">
                        <x-heroicon-m-exclamation-triangle class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" />
                        <p class="text-sm text-red-800 dark:text-red-200">
                            Critical issues detected. Please review system health immediately.
                        </p>
                    </div>
                </div>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>