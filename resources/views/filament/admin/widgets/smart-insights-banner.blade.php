<div>
    @if($this->shouldDisplay())
        <x-filament-widgets::widget>
            <div class="space-y-2">
                @foreach($this->getInsights() as $insight)
                    <div class="flex items-center justify-between p-3 rounded-lg 
                        @if($insight['type'] === 'warning') bg-amber-50 border border-amber-200 dark:bg-amber-900/20 dark:border-amber-800
                        @elseif($insight['type'] === 'danger') bg-red-50 border border-red-200 dark:bg-red-900/20 dark:border-red-800
                        @elseif($insight['type'] === 'success') bg-green-50 border border-green-200 dark:bg-green-900/20 dark:border-green-800
                        @else bg-blue-50 border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800
                        @endif">
                        
                        <div class="flex items-center gap-3">
                            {{-- Icon --}}
                            <x-filament::icon 
                                :icon="$insight['icon']"
                                class="h-5 w-5 flex-shrink-0
                                    @if($insight['type'] === 'warning') text-amber-600 dark:text-amber-400
                                    @elseif($insight['type'] === 'danger') text-red-600 dark:text-red-400
                                    @elseif($insight['type'] === 'success') text-green-600 dark:text-green-400
                                    @else text-blue-600 dark:text-blue-400
                                    @endif"
                            />
                            
                            {{-- Message --}}
                            <span class="text-sm font-medium 
                                @if($insight['type'] === 'warning') text-amber-900 dark:text-amber-100
                                @elseif($insight['type'] === 'danger') text-red-900 dark:text-red-100
                                @elseif($insight['type'] === 'success') text-green-900 dark:text-green-100
                                @else text-blue-900 dark:text-blue-100
                                @endif">
                                {{ $insight['message'] }}
                            </span>
                        </div>
                        
                        {{-- Action Button --}}
                        @if(isset($insight['action']))
                            <a href="{{ $insight['action']['url'] }}" 
                               class="text-sm font-medium px-3 py-1 rounded-md transition-colors
                                    @if($insight['type'] === 'warning') 
                                        text-amber-700 hover:bg-amber-100 dark:text-amber-300 dark:hover:bg-amber-900/40
                                    @elseif($insight['type'] === 'danger') 
                                        text-red-700 hover:bg-red-100 dark:text-red-300 dark:hover:bg-red-900/40
                                    @elseif($insight['type'] === 'success') 
                                        text-green-700 hover:bg-green-100 dark:text-green-300 dark:hover:bg-green-900/40
                                    @else 
                                        text-blue-700 hover:bg-blue-100 dark:text-blue-300 dark:hover:bg-blue-900/40
                                    @endif">
                                {{ $insight['action']['label'] }} →
                            </a>
                        @endif
                        
                        {{-- Dismiss Button --}}
                        <button type="button" 
                                onclick="this.closest('.flex').style.display='none'"
                                class="p-1 rounded-md transition-colors
                                    @if($insight['type'] === 'warning') 
                                        text-amber-500 hover:bg-amber-100 dark:hover:bg-amber-900/40
                                    @elseif($insight['type'] === 'danger') 
                                        text-red-500 hover:bg-red-100 dark:hover:bg-red-900/40
                                    @elseif($insight['type'] === 'success') 
                                        text-green-500 hover:bg-green-100 dark:hover:bg-green-900/40
                                    @else 
                                        text-blue-500 hover:bg-blue-100 dark:hover:bg-blue-900/40
                                    @endif">
                            <x-filament::icon 
                                icon="heroicon-o-x-mark"
                                class="h-4 w-4"
                            />
                        </button>
                    </div>
                @endforeach
            </div>
        </x-filament-widgets::widget>
    @endif
</div>