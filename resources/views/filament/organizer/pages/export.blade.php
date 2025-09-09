<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-2">
        {{-- Revenue Export Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">
                Revenue Export
            </h3>
            
            <form wire:submit.prevent="exportRevenue">
                {{ $this->revenueForm }}
                
                <div class="mt-3">
                    <x-filament::button type="submit" icon="heroicon-m-arrow-down-tray" size="sm">
                        Export CSV
                    </x-filament::button>
                </div>
            </form>
        </div>

        {{-- Booking Export Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">
                Booking Export
            </h3>
            
            <form wire:submit.prevent="exportBookings">
                {{ $this->bookingForm }}
                
                <div class="mt-3">
                    <x-filament::button type="submit" icon="heroicon-m-arrow-down-tray" size="sm">
                        Export CSV
                    </x-filament::button>
                </div>
            </form>
        </div>
    </div>
</x-filament-panels::page>