<x-filament-panels::page>
    @php
        $data = $this->getRevenueData();
        $currency = $data['currency'];
        $summary = $data['summary'];
    @endphp
    
    {{-- Summary Cards - All in ONE horizontal row --}}
    <div class="flex gap-4 mb-6 overflow-x-auto">
        {{-- Gross Revenue Card --}}
        <div class="flex-1 min-w-[200px] bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Gross Revenue</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                    {{ $currency }} {{ number_format($summary['gross_revenue'], 2) }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    {{ $summary['total_bookings'] }} bookings
                </p>
            </div>
        </div>
        
        {{-- Net Revenue Card --}}
        <div class="flex-1 min-w-[200px] bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Net Revenue</p>
                <p class="text-xl font-bold text-green-600 dark:text-green-400 mt-1">
                    {{ $currency }} {{ number_format($summary['net_revenue'], 2) }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    After all fees
                </p>
            </div>
        </div>
        
        {{-- Total Fees Card --}}
        <div class="flex-1 min-w-[200px] bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Fees</p>
                <p class="text-xl font-bold text-red-600 dark:text-red-400 mt-1">
                    {{ $currency }} {{ number_format($summary['total_fees'], 2) }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    Processing + Commission
                </p>
            </div>
        </div>
        
        {{-- Average Booking Card --}}
        <div class="flex-1 min-w-[200px] bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Avg Booking Value</p>
                <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">
                    {{ $currency }} {{ number_format($summary['avg_booking_value'], 2) }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    {{ $summary['total_tickets'] }} tickets sold
                </p>
            </div>
        </div>
    </div>
    
    {{-- Filters - Right below the cards --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 mb-6">
        {{ $this->form }}
    </div>
    
    {{-- Revenue Chart - Full width --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Revenue Trend - {{ $data['period_label'] }}
            </h3>
            <div class="flex items-center gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <span class="text-gray-600 dark:text-gray-400">Revenue</span>
                </div>
            </div>
        </div>
        <div class="h-80">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
    
    {{-- Revenue Breakdown and Top Listings - Side by side in ONE ROW --}}
    <div class="flex gap-6 items-stretch">
        {{-- Revenue Breakdown - Left Column --}}
        <div class="flex-1 bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 flex flex-col">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Revenue Breakdown
            </h3>
            
            <div class="space-y-4">
                {{-- Gross Sales --}}
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Gross Sales</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $currency }} {{ number_format($summary['gross_revenue'], 2) }}
                    </span>
                </div>
                
                @if($summary['total_paystack_fees'] > 0)
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-red-600 dark:text-red-400">
                        Processing Fees
                    </span>
                    <span class="text-sm font-semibold text-red-600 dark:text-red-400">
                        -{{ $currency }} {{ number_format($summary['total_paystack_fees'], 2) }}
                    </span>
                </div>
                @endif
                
                @if($summary['total_commission'] > 0)
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-orange-600 dark:text-orange-400">
                        Platform Commission
                    </span>
                    <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">
                        -{{ $currency }} {{ number_format($summary['total_commission'], 2) }}
                    </span>
                </div>
                @endif
                
                @if($summary['total_processing_fees'] > 0)
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-yellow-600 dark:text-yellow-400">
                        Other Fees
                    </span>
                    <span class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">
                        -{{ $currency }} {{ number_format($summary['total_processing_fees'], 2) }}
                    </span>
                </div>
                @endif
                
                {{-- Divider --}}
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <div class="flex justify-between items-center">
                        <span class="text-base font-semibold text-gray-900 dark:text-white">
                            Net Revenue
                        </span>
                        <span class="text-lg font-bold text-green-600 dark:text-green-400">
                            {{ $currency }} {{ number_format($summary['net_revenue'], 2) }}
                        </span>
                    </div>
                </div>
                
                {{-- Additional metrics --}}
                <div class="pt-4 space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Total Bookings</span>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ number_format($summary['total_bookings']) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Total Tickets</span>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ number_format($summary['total_tickets']) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Avg Booking</span>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ $currency }} {{ number_format($summary['avg_booking_value'], 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Top Listings - Right Column --}}
        <div class="flex-1 bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 flex flex-col">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Top Listings by Revenue
            </h3>
            
            @if(!$this->event_id && count($data['event_breakdown']) > 0)
                <div class="space-y-3">
                    @foreach(array_slice($data['event_breakdown'], 0, 5) as $index => $event)
                    <div class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 text-xs font-semibold text-gray-600 dark:text-gray-400">
                                {{ $index + 1 }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ Str::limit($event['title'], 35) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $event['bookings'] }} bookings • {{ $event['tickets'] }} tickets
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $currency }} {{ number_format($event['revenue'], 0) }}
                            </p>
                            <p class="text-xs text-green-600 dark:text-green-400">
                                Net: {{ $currency }} {{ number_format($event['net'], 0) }}
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                @if(count($data['event_breakdown']) > 5)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                        Showing top 5 of {{ count($data['event_breakdown']) }} listings
                    </p>
                </div>
                @endif
            @elseif($this->event_id)
                <div class="text-center py-4">
                    <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Showing data for selected listing only
                    </p>
                </div>
            @else
                <div class="text-center py-4">
                    <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        No listings found for this period
                    </p>
                </div>
            @endif
        </div>
    </div>
    
    {{-- Chart JavaScript --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart');
            if (!ctx) return;
            
            const chartData = @json($data['chart_data']);
            
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: chartData.map(d => d.date),
                    datasets: [{
                        label: 'Revenue',
                        data: chartData.map(d => d.gross),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.05)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: 'rgb(59, 130, 246)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
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
                                    const index = context.dataIndex;
                                    const bookings = chartData[index].bookings;
                                    return [
                                        'Revenue: {{ $currency }} ' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}),
                                        'Bookings: ' + bookings
                                    ];
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
                                    return '{{ $currency }} ' + value.toLocaleString();
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