<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span>Geographic Heat Map</span>
                    @if(!$isLoading && isset($summary['total_countries']) && $summary['total_countries'] > 0)
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $summary['total_countries'] }} active countries
                        </span>
                    @endif
                </div>
                @if(!$isLoading && $lastUpdated)
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Updated {{ $lastUpdated->diffForHumans() }}
                    </span>
                @endif
            </div>
        </x-slot>

        @if($isLoading)
            {{-- Loading state --}}
            <div class="flex items-center justify-center h-96">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500 mx-auto mb-4"></div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Loading geographic data...</p>
                </div>
            </div>
        @else
            <div class="space-y-4">
                {{-- Compact Heat Map Grid --}}
                <div class="bg-gradient-to-br from-blue-50 to-green-50 dark:from-gray-800 dark:to-gray-900 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3">
                        Activity Heat Map
                    </h3>
                    
                    {{-- Compact grid visualization --}}
                    <div class="grid grid-cols-8 gap-1">
                        @php
                            $compactCountries = [
                                'EG' => 'Egypt', 'LY' => 'Libya', 'TN' => 'Tunisia', 'DZ' => 'Algeria',
                                'MA' => 'Morocco', 'SD' => 'Sudan', 'NG' => 'Nigeria', 'GH' => 'Ghana',
                                'SN' => 'Senegal', 'CI' => 'Côte d\'Ivoire', 'ML' => 'Mali', 'BF' => 'Burkina Faso',
                                'KE' => 'Kenya', 'ET' => 'Ethiopia', 'UG' => 'Uganda', 'TZ' => 'Tanzania',
                                'RW' => 'Rwanda', 'SO' => 'Somalia', 'CM' => 'Cameroon', 'CD' => 'Congo DRC',
                                'ZA' => 'South Africa', 'ZW' => 'Zimbabwe', 'ZM' => 'Zambia', 'BW' => 'Botswana',
                            ];
                        @endphp
                        
                        @foreach($compactCountries as $code => $name)
                            @php
                                $data = $mapData[$code] ?? null;
                                $heatLevel = $data['heat_level'] ?? 'very-low';
                                $bgColor = match($heatLevel) {
                                    'very-high' => 'bg-red-500',
                                    'high' => 'bg-orange-500',
                                    'medium' => 'bg-yellow-500',
                                    'low' => 'bg-green-500',
                                    default => 'bg-gray-300 dark:bg-gray-600'
                                };
                            @endphp
                            <div 
                                class="group relative"
                                title="{{ $name }}: {{ number_format($data['users'] ?? 0) }} users"
                            >
                                <div class="aspect-square rounded {{ $bgColor }} opacity-70 hover:opacity-100 transition-opacity cursor-pointer flex items-center justify-center">
                                    <span class="text-white font-bold text-[10px]">{{ $code }}</span>
                                </div>
                                
                                {{-- Compact tooltip --}}
                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                                    <div class="bg-gray-900 text-white text-xs rounded py-1 px-2 whitespace-nowrap">
                                        <div class="font-semibold">{{ $name }}</div>
                                        <div class="text-[10px]">{{ number_format($data['users'] ?? 0) }} users</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                {{-- Top Countries List --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3">
                        Top 5 Countries
                    </h3>
                    
                    @if(count($topCountries) > 0)
                        <div class="space-y-2">
                            @foreach(array_slice($topCountries, 0, 5, true) as $countryName => $stats)
                                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-800">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full {{ 
                                            $stats['heat_level'] === 'very-high' ? 'bg-red-500' :
                                            ($stats['heat_level'] === 'high' ? 'bg-orange-500' :
                                            ($stats['heat_level'] === 'medium' ? 'bg-yellow-500' :
                                            ($stats['heat_level'] === 'low' ? 'bg-green-500' :
                                            'bg-gray-400')))
                                        }}"></div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $countryName }}
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ number_format($stats['users']) }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            users
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            No activity data available yet
                        </p>
                    @endif
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>