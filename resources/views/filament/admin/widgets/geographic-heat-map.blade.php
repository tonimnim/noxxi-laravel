<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Geographic Distribution
        </x-slot>
        
        <div class="space-y-4">
            {{-- Map Placeholder --}}
            <div class="relative bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-800 dark:to-gray-900 rounded-lg p-6 h-48 flex items-center justify-center">
                <div class="text-center">
                    <x-filament::icon 
                        icon="heroicon-o-map"
                        class="mx-auto h-12 w-12 text-indigo-400 dark:text-indigo-500 mb-2"
                    />
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Interactive Africa Map
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                        Showing booking density across Africa
                    </p>
                </div>
                
                {{-- Sample heat indicators --}}
                <div class="absolute top-4 right-4">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-gray-600 dark:text-gray-400">Low</span>
                        <div class="flex gap-1">
                            <div class="w-3 h-3 bg-blue-200 rounded"></div>
                            <div class="w-3 h-3 bg-blue-400 rounded"></div>
                            <div class="w-3 h-3 bg-blue-600 rounded"></div>
                            <div class="w-3 h-3 bg-blue-800 rounded"></div>
                        </div>
                        <span class="text-gray-600 dark:text-gray-400">High</span>
                    </div>
                </div>
            </div>
            
            {{-- City Statistics --}}
            <div>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    Top Cities by Activity
                </h4>
                
                <div class="space-y-2">
                    @foreach($this->getTopCountries() as $city)
                        <div class="relative">
                            <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        {{-- City Icon --}}
                                        <x-filament::icon 
                                            icon="heroicon-o-map-pin"
                                            class="h-4 w-4 text-gray-400"
                                        />
                                        
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $city['name'] }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($city['bookings']) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        bookings
                                    </p>
                                </div>
                            </div>
                            
                            {{-- Intensity Bar --}}
                            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 rounded-b-lg overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-400 to-indigo-600 transition-all duration-500"
                                     style="width: {{ $city['intensity'] }}%">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            {{-- Summary Stats --}}
            <div class="grid grid-cols-3 gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                @php
                    $totalCities = count($this->getCountryData());
                    $totalBookings = array_sum(array_column($this->getCountryData(), 'bookings'));
                    $totalRevenue = array_sum(array_column($this->getCountryData(), 'revenue'));
                @endphp
                
                <div class="text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $totalCities }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Cities</p>
                </div>
                
                <div class="text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($totalBookings) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Bookings</p>
                </div>
                
                <div class="text-center">
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $totalRevenue >= 1000000 ? 'KES ' . round($totalRevenue / 1000000, 1) . 'M' : 'KES ' . round($totalRevenue / 1000) . 'K' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Revenue</p>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>