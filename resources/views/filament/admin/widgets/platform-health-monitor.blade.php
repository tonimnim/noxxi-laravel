<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Platform Health Monitor
        </x-slot>
        
        @php
            $health = $this->getHealthData();
            $overallStatus = $health['overall_status'];
            
            $statusColors = [
                'operational' => 'success',
                'degraded' => 'warning', 
                'critical' => 'danger',
                'down' => 'danger',
                'healthy' => 'success',
                'slow' => 'warning',
                'unknown' => 'gray',
            ];
            
            $statusIcons = [
                'operational' => 'heroicon-o-check-circle',
                'degraded' => 'heroicon-o-exclamation-triangle',
                'critical' => 'heroicon-o-x-circle',
                'down' => 'heroicon-o-x-circle',
                'healthy' => 'heroicon-o-check-circle',
                'slow' => 'heroicon-o-clock',
                'unknown' => 'heroicon-o-question-mark-circle',
            ];
        @endphp
        
        <div class="space-y-4">
            {{-- Overall Status --}}
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Overall System Status</span>
                <div class="flex items-center gap-2">
                    <x-filament::icon 
                        :icon="$statusIcons[$overallStatus]"
                        class="h-5 w-5 text-{{ $statusColors[$overallStatus] }}-500"
                    />
                    <span class="text-sm font-semibold text-{{ $statusColors[$overallStatus] }}-600 dark:text-{{ $statusColors[$overallStatus] }}-400 capitalize">
                        {{ $overallStatus }}
                    </span>
                </div>
            </div>
            
            {{-- Payment Gateways --}}
            <div>
                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    Payment Gateways
                </h4>
                <div class="space-y-2">
                    @foreach($health['payment_gateways'] as $gateway)
                        <div class="flex items-center justify-between py-2 px-3 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-2 rounded-full bg-{{ $statusColors[$gateway['status']] }}-500 animate-pulse"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $gateway['name'] }}</span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $gateway['response_time'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            
            {{-- Queue Status --}}
            <div>
                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                    Queue Status
                </h4>
                <div class="flex items-center justify-between py-2 px-3 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="h-2 w-2 rounded-full bg-{{ $statusColors[$health['queue_status']['status']] }}-500"></div>
                        <div>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Jobs Processing</span>
                            <span class="ml-2 text-xs text-gray-500">{{ $health['queue_status']['processing'] }}</span>
                        </div>
                    </div>
                    @if($health['queue_status']['failed'] > 0)
                        <span class="text-xs font-medium text-red-600 dark:text-red-400">
                            {{ $health['queue_status']['failed'] }} failed
                        </span>
                    @endif
                </div>
            </div>
            
            {{-- Database & API --}}
            <div class="grid grid-cols-2 gap-2">
                <div class="py-2 px-3 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600 dark:text-gray-400">Database</span>
                        <div class="h-2 w-2 rounded-full bg-{{ $statusColors[$health['database']['status']] }}-500"></div>
                    </div>
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ $health['database']['response_time'] }}
                    </span>
                </div>
                
                <div class="py-2 px-3 bg-white dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-600 dark:text-gray-400">API</span>
                        <div class="h-2 w-2 rounded-full bg-{{ $statusColors[$health['api_health']['status']] }}-500"></div>
                    </div>
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ $health['api_health']['response_time'] }}
                    </span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>