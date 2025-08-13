@php
    $data = $this->getPayoutData();
@endphp

<div>
    <x-filament::card>
        <div class="space-y-4">
            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Payouts</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Manage your earnings</p>
                </div>
                <x-filament::button size="sm" outlined>
                    View all
                </x-filament::button>
            </div>
            
            {{-- Balance Cards --}}
            <div class="grid grid-cols-2 gap-4">
                {{-- Available Balance --}}
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-green-600 dark:text-green-400">Available Balance</p>
                            <p class="text-xl font-semibold text-green-900 dark:text-green-100">
                                {{ $data['currency'] }} {{ number_format($data['available_balance'], 0) }}
                            </p>
                        </div>
                        <x-heroicon-o-banknotes class="w-8 h-8 text-green-500" />
                    </div>
                </div>
                
                {{-- Pending Payouts --}}
                <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-amber-600 dark:text-amber-400">Pending</p>
                            <p class="text-xl font-semibold text-amber-900 dark:text-amber-100">
                                {{ $data['currency'] }} {{ number_format($data['pending_payouts'], 0) }}
                            </p>
                        </div>
                        <x-heroicon-o-clock class="w-8 h-8 text-amber-500" />
                    </div>
                </div>
            </div>
            
            {{-- Next Payout Info --}}
            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-calendar-days class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        Next payout scheduled for <span class="font-semibold">{{ $data['next_payout_date'] }}</span>
                    </p>
                </div>
            </div>
            
            
            {{-- Request Payout Button --}}
            <x-filament::button class="w-full" icon="heroicon-o-banknotes">
                Request Payout
            </x-filament::button>
        </div>
    </x-filament::card>
</div>