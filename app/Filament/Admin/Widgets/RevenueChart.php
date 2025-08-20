<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Trend';
    
    protected static ?int $sort = 3;
    
    protected static bool $isLazy = true; // Lazy load chart
    
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'md' => 2,
        'lg' => 2,
        'xl' => 3,
    ];
    
    protected static ?string $pollingInterval = null; // Disable polling
    
    public ?string $filter = '30';
    
    protected static ?string $maxHeight = '400px';
    
    public function getHeading(): string|HtmlString|null
    {
        return static::$heading;
    }
    
    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
        ];
    }
    
    protected function getData(): array
    {
        $days = (int) $this->filter;
        
        Cache::put('admin.revenue_chart.last_update', now(), 300);
        
        $data = Cache::remember("admin.revenue_chart.{$days}", 300, function () use ($days) {
            return DB::table('bookings')
                ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays($days))
                ->where('payment_status', 'paid')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        });
        
        $previousData = Cache::remember("admin.revenue_chart.previous.{$days}", 300, function () use ($days) {
            return DB::table('bookings')
                ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
                ->whereBetween('created_at', [now()->subDays($days * 2), now()->subDays($days)])
                ->where('payment_status', 'paid')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('total', 'date');
        });
        
        $labels = [];
        $currentRevenue = [];
        $previousRevenue = [];
        $transactions = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M d');
            
            $dayData = $data->firstWhere('date', $date);
            $currentRevenue[] = $dayData ? round($dayData->total) : 0;
            $transactions[] = $dayData ? $dayData->count : 0;
            
            $prevDate = now()->subDays($i + $days)->format('Y-m-d');
            $previousRevenue[] = isset($previousData[$prevDate]) ? round($previousData[$prevDate]) : 0;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Current Period',
                    'data' => $currentRevenue,
                    'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                    'borderColor' => 'rgb(79, 70, 229)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                    'fill' => true,
                ],
                [
                    'label' => 'Previous Period',
                    'data' => $previousRevenue,
                    'backgroundColor' => 'rgba(156, 163, 175, 0.1)',
                    'borderColor' => 'rgb(156, 163, 175)',
                    'borderWidth' => 1,
                    'borderDash' => [5, 5],
                    'tension' => 0.3,
                    'fill' => false,
                ],
            ],
            'labels' => $labels,
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => "function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += 'KES ' + new Intl.NumberFormat().format(context.parsed.y);
                            }
                            return label;
                        }",
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'callback' => "function(value) {
                            if (value >= 1000000) {
                                return 'KES ' + (value / 1000000) + 'M';
                            } else if (value >= 1000) {
                                return 'KES ' + (value / 1000) + 'K';
                            }
                            return 'KES ' + value;
                        }",
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }
    
    public function getDescription(): string|HtmlString|null
    {
        $days = (int) $this->filter;
        $current = Cache::remember("admin.revenue_total.{$days}", 300, function () use ($days) {
            return DB::table('bookings')
                ->where('created_at', '>=', now()->subDays($days))
                ->where('payment_status', 'paid')
                ->sum('total_amount');
        });
        
        $previous = Cache::remember("admin.revenue_total.previous.{$days}", 300, function () use ($days) {
            return DB::table('bookings')
                ->whereBetween('created_at', [now()->subDays($days * 2), now()->subDays($days)])
                ->where('payment_status', 'paid')
                ->sum('total_amount');
        });
        
        $change = $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : 0;
        $changeIcon = $change >= 0 ? '📈' : '📉';
        $changeText = abs($change) . '%';
        $changeLabel = $change >= 0 ? 'increase' : 'decrease';
        
        return new HtmlString(
            '<div class="flex items-center justify-between">' .
            '<span class="text-sm text-gray-600 dark:text-gray-400">Total: <strong>KES ' . number_format($current, 0) . '</strong></span>' .
            '<span class="text-xs px-2 py-1 rounded-full ' . 
            ($change >= 0 ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100') . '">' .
            $changeIcon . ' ' . $changeText . ' ' . $changeLabel . '</span>' .
            '</div>'
        );
    }
}