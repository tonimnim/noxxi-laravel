<x-filament-panels::page>
    @php
        $organizer = auth()->user()->organizer;
    @endphp
    
    {{-- Summary Cards - All in ONE horizontal row --}}
    <div class="flex gap-4 mb-6 overflow-x-auto">
        {{-- Available Balance Card --}}
        <div class="flex-1 min-w-[200px] bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Available Balance</p>
                <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">
                    {{ $balanceData['currency'] }} {{ number_format($balanceData['available_balance'], 2) }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    @if($balanceData['available_balance'] >= 1000)
                        <span class="text-green-600">Min: KES 1,000</span>
                    @else
                        <span class="text-red-600">Min: KES 1,000</span>
                    @endif
                </p>
            </div>
        </div>
        
        {{-- Pending Payouts Card --}}
        <div class="flex-1 min-w-[200px] bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Pending Payouts</p>
                <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
                    {{ $balanceData['currency'] }} {{ number_format($stats['pending_payouts'], 2) }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    @if($stats['pending_payouts'] > 0)
                        No pending
                    @else
                        No pending
                    @endif
                </p>
            </div>
        </div>
        
        {{-- Total Paid Out Card --}}
        <div class="flex-1 min-w-[200px] bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Paid Out</p>
                <p class="text-xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                    {{ $balanceData['currency'] }} {{ number_format($stats['total_paid_out'], 2) }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    {{ $stats['payout_count'] }} payouts
                </p>
            </div>
        </div>
        
        {{-- This Month Card --}}
        <div class="flex-1 min-w-[200px] bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">This Month</p>
                <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">
                    {{ $balanceData['currency'] }} {{ number_format($stats['this_month_earnings'], 2) }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    Gross earnings
                </p>
            </div>
        </div>
    </div>
    
    {{-- Payout History Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Payout History
            </h3>
        </div>
        <div class="overflow-hidden">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>