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
    
    {{-- Earnings Chart - Full width --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Daily Earnings (Last 30 Days)
            </h3>
            <div class="flex items-center gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <span class="text-gray-600 dark:text-gray-400">Earnings</span>
                </div>
            </div>
        </div>
        <div class="h-80">
            <canvas id="earningsChart"></canvas>
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
    
    {{-- Chart JavaScript --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('earningsChart');
            if (!ctx) return;
            
            const chartData = @json($chartData);
            
            new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: chartData.map(d => d.date),
                    datasets: [{
                        label: 'Earnings',
                        data: chartData.map(d => d.earnings),
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1,
                        borderRadius: 4,
                        barThickness: 20,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleColor: '#fff',
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyColor: '#fff',
                            bodyFont: {
                                size: 13
                            },
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1,
                            displayColors: false,
                            callbacks: {
                                title: function(context) {
                                    return context[0].label;
                                },
                                label: function(context) {
                                    return 'Earnings: {{ $balanceData['currency'] }} ' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6b7280',
                                font: {
                                    size: 11
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(156, 163, 175, 0.1)',
                                drawBorder: false
                            },
                            ticks: {
                                color: '#6b7280',
                                font: {
                                    size: 11
                                },
                                callback: function(value) {
                                    return '{{ $balanceData['currency'] }} ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</x-filament-panels::page>