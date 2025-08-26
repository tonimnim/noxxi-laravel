<x-filament-panels::page>
    @php
        $data = $this->getReconciliationData();
        $currency = $data['summary']['currency'];
    @endphp
    
    <div class="space-y-6">
        {{-- Date Range Filter --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <form wire:submit="updateDateRange" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
                    <input type="date" wire:model="dateFrom" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
                    <input type="date" wire:model="dateTo" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    Update Report
                </button>
            </form>
        </div>
        
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Available Balance</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ $currency }} {{ number_format($data['summary']['available_balance'], 2) }}
                </p>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Gross Revenue</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ $currency }} {{ number_format($data['summary']['gross_revenue'], 2) }}
                </p>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Refunds</h3>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-2">
                    -{{ $currency }} {{ number_format($data['summary']['total_refunds'], 2) }}
                </p>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Adjusted Revenue</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    {{ $currency }} {{ number_format($data['summary']['adjusted_revenue'], 2) }}
                </p>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Platform Commission</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                    -{{ $currency }} {{ number_format($data['summary']['total_commission'], 2) }}
                </p>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Net Revenue</h3>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-2">
                    {{ $currency }} {{ number_format($data['summary']['net_revenue'], 2) }}
                </p>
            </div>
        </div>
        
        {{-- Period Activity --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Activity for {{ $data['period']['from'] }} - {{ $data['period']['to'] }}
            </h2>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Ticket Sales --}}
                <div class="border rounded-lg p-4 dark:border-gray-700">
                    <h3 class="font-medium text-gray-700 dark:text-gray-300 mb-3">Ticket Sales</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Transactions:</dt>
                            <dd class="font-medium">{{ $data['period_data']['ticket_sales']['count'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Gross Amount:</dt>
                            <dd class="font-medium">{{ $currency }} {{ number_format($data['period_data']['ticket_sales']['gross'], 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Commission:</dt>
                            <dd class="font-medium text-red-600">-{{ $currency }} {{ number_format($data['period_data']['ticket_sales']['commission'], 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Gateway Fees:</dt>
                            <dd class="font-medium text-red-600">-{{ $currency }} {{ number_format($data['period_data']['ticket_sales']['fees'], 2) }}</dd>
                        </div>
                        <div class="flex justify-between border-t pt-2 dark:border-gray-600">
                            <dt class="text-gray-600 dark:text-gray-400 font-semibold">Net Amount:</dt>
                            <dd class="font-bold text-green-600">{{ $currency }} {{ number_format($data['period_data']['ticket_sales']['net'], 2) }}</dd>
                        </div>
                    </dl>
                </div>
                
                {{-- Refunds --}}
                <div class="border rounded-lg p-4 dark:border-gray-700">
                    <h3 class="font-medium text-gray-700 dark:text-gray-300 mb-3">Refunds</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Transactions:</dt>
                            <dd class="font-medium">{{ $data['period_data']['refunds']['count'] }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Gross Amount:</dt>
                            <dd class="font-medium text-red-600">-{{ $currency }} {{ number_format($data['period_data']['refunds']['gross'], 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Commission Returned:</dt>
                            <dd class="font-medium text-green-600">+{{ $currency }} {{ number_format($data['period_data']['refunds']['commission'], 2) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-400">Fees Returned:</dt>
                            <dd class="font-medium text-green-600">+{{ $currency }} {{ number_format($data['period_data']['refunds']['fees'], 2) }}</dd>
                        </div>
                        <div class="flex justify-between border-t pt-2 dark:border-gray-600">
                            <dt class="text-gray-600 dark:text-gray-400 font-semibold">Net Impact:</dt>
                            <dd class="font-bold text-red-600">-{{ $currency }} {{ number_format($data['period_data']['refunds']['net'], 2) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
            
            {{-- Period Net Position --}}
            <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">Period Net Position:</span>
                    <span class="text-2xl font-bold {{ $data['period_data']['net_position'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $currency }} {{ number_format($data['period_data']['net_position'], 2) }}
                    </span>
                </div>
            </div>
        </div>
        
        {{-- Refund Requests Status --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Refund Requests Status</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <p class="text-2xl font-bold text-yellow-600">{{ $data['refund_requests']['pending']['count'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Pending</p>
                    <p class="text-xs text-gray-500">{{ $currency }} {{ number_format($data['refund_requests']['pending']['amount'], 0) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ $data['refund_requests']['approved']['count'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Approved</p>
                    <p class="text-xs text-gray-500">{{ $currency }} {{ number_format($data['refund_requests']['approved']['amount'], 0) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-red-600">{{ $data['refund_requests']['rejected']['count'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Rejected</p>
                    <p class="text-xs text-gray-500">{{ $currency }} {{ number_format($data['refund_requests']['rejected']['amount'], 0) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-green-600">{{ $data['refund_requests']['processed']['count'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Processed</p>
                    <p class="text-xs text-gray-500">{{ $currency }} {{ number_format($data['refund_requests']['processed']['amount'], 0) }}</p>
                </div>
            </div>
        </div>
        
        {{-- Event Breakdown --}}
        @if(count($data['event_breakdown']) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Event Breakdown</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="text-left py-2 px-4 font-medium text-gray-700 dark:text-gray-300">Event</th>
                            <th class="text-right py-2 px-4 font-medium text-gray-700 dark:text-gray-300">Sales</th>
                            <th class="text-right py-2 px-4 font-medium text-gray-700 dark:text-gray-300">Refunds</th>
                            <th class="text-right py-2 px-4 font-medium text-gray-700 dark:text-gray-300">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['event_breakdown'] as $event)
                        <tr class="border-b dark:border-gray-700">
                            <td class="py-2 px-4">{{ $event['title'] }}</td>
                            <td class="text-right py-2 px-4">
                                <span class="text-green-600">{{ $currency }} {{ number_format($event['sales_amount'], 2) }}</span>
                                <span class="text-xs text-gray-500">({{ $event['sales_count'] }})</span>
                            </td>
                            <td class="text-right py-2 px-4">
                                @if($event['refund_count'] > 0)
                                <span class="text-red-600">-{{ $currency }} {{ number_format($event['refund_amount'], 2) }}</span>
                                <span class="text-xs text-gray-500">({{ $event['refund_count'] }})</span>
                                @else
                                <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="text-right py-2 px-4 font-semibold">
                                <span class="{{ $event['net_amount'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $currency }} {{ number_format($event['net_amount'], 2) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>