<?php

namespace App\Filament\Organizer\Pages;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Revenue extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Revenue';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.organizer.pages.revenue';

    public function getHeading(): string
    {
        return ''; // Remove the heading
    }

    public function getSubheading(): ?string
    {
        return null; // Remove the subheading
    }

    // Form fields for filtering
    public ?string $period = 'this_month';

    public ?string $event_id = null;

    public ?string $date_from = null;

    public ?string $date_to = null;

    // Cache TTL in seconds (5 minutes)
    private const CACHE_TTL = 300;

    public function mount(): void
    {
        $this->form->fill([
            'period' => 'this_month',
            'date_from' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'date_to' => Carbon::now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('period')
                    ->label('Period')
                    ->options([
                        'today' => 'Today',
                        'yesterday' => 'Yesterday',
                        'this_week' => 'This Week',
                        'last_week' => 'Last Week',
                        'this_month' => 'This Month',
                        'last_month' => 'Last Month',
                        'this_year' => 'This Year',
                        'custom' => 'Custom Range',
                    ])
                    ->reactive()
                    ->afterStateUpdated(function ($state) {
                        if ($state !== null) {
                            $this->updateDateRange($state);
                            $this->clearRevenueCache();
                        }
                    }),

                DatePicker::make('date_from')
                    ->label('From')
                    ->visible(fn () => $this->period === 'custom')
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->clearRevenueCache()),

                DatePicker::make('date_to')
                    ->label('To')
                    ->visible(fn () => $this->period === 'custom')
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->clearRevenueCache()),

                Select::make('event_id')
                    ->label('Event')
                    ->placeholder('All Events')
                    ->options(function () {
                        return Cache::remember(
                            'organizer_events_'.Auth::user()->organizer->id,
                            self::CACHE_TTL,
                            fn () => Event::where('organizer_id', Auth::user()->organizer->id)
                                ->select('id', 'title')
                                ->orderBy('title')
                                ->pluck('title', 'id')
                        );
                    })
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->clearRevenueCache()),
            ])
            ->columns(4);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    // TODO: Implement CSV export
                    Notification::make()
                        ->title('Export Started')
                        ->body('Your revenue data is being prepared for download.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function updateDateRange(string $period): void
    {
        switch ($period) {
            case 'today':
                $this->date_from = Carbon::today()->format('Y-m-d');
                $this->date_to = Carbon::today()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->date_from = Carbon::yesterday()->format('Y-m-d');
                $this->date_to = Carbon::yesterday()->format('Y-m-d');
                break;
            case 'this_week':
                $this->date_from = Carbon::now()->startOfWeek()->format('Y-m-d');
                $this->date_to = Carbon::now()->endOfWeek()->format('Y-m-d');
                break;
            case 'last_week':
                $this->date_from = Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d');
                $this->date_to = Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d');
                break;
            case 'this_month':
                $this->date_from = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->date_to = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
            case 'last_month':
                $this->date_from = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->date_to = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'this_year':
                $this->date_from = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->date_to = Carbon::now()->endOfYear()->format('Y-m-d');
                break;
        }
    }

    /**
     * Get revenue data with performance optimization using proper indexing and caching.
     */
    public function getRevenueData(): array
    {
        $organizerId = Auth::user()->organizer?->id;
        if (! $organizerId) {
            return $this->getEmptyData();
        }

        $cacheKey = "revenue_data.{$organizerId}.{$this->period}.{$this->event_id}.{$this->date_from}.{$this->date_to}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($organizerId) {
            // Start date range for queries
            $startDate = Carbon::parse($this->date_from)->startOfDay();
            $endDate = Carbon::parse($this->date_to)->endOfDay();

            // Optimized booking query with proper indexing
            $bookingQuery = Booking::query()
                ->join('events', 'bookings.event_id', '=', 'events.id')
                ->where('events.organizer_id', $organizerId)
                ->where('bookings.payment_status', 'paid')
                ->whereBetween('bookings.created_at', [$startDate, $endDate])
                ->when($this->event_id, fn ($q) => $q->where('bookings.event_id', $this->event_id));

            // Get summary statistics in one query
            $summary = $bookingQuery->clone()
                ->selectRaw('
                    COUNT(DISTINCT bookings.id) as total_bookings,
                    SUM(bookings.quantity) as total_tickets,
                    SUM(bookings.total_amount) as gross_revenue,
                    SUM(bookings.subtotal) as subtotal,
                    AVG(bookings.total_amount) as avg_booking_value
                ')
                ->first();

            // Get transaction fees and commission in one optimized query
            $transactionStats = Transaction::query()
                ->where('organizer_id', $organizerId)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->when($this->event_id, function ($q) {
                    return $q->whereHas('booking', fn ($b) => $b->where('event_id', $this->event_id));
                })
                ->selectRaw('
                    COALESCE(SUM(paystack_fee), 0) as total_paystack_fees,
                    COALESCE(SUM(platform_commission), 0) as total_commission,
                    COALESCE(SUM(payment_processing_fee), 0) as total_processing_fees,
                    COALESCE(SUM(net_amount), 0) as total_net_amount
                ')
                ->first();

            // Get revenue by payment method
            $revenueByMethod = $bookingQuery->clone()
                ->select('bookings.payment_method')
                ->selectRaw('SUM(bookings.total_amount) as amount')
                ->selectRaw('COUNT(bookings.id) as count')
                ->groupBy('bookings.payment_method')
                ->get()
                ->map(fn ($item) => [
                    'method' => $item->payment_method,
                    'amount' => $item->amount,
                    'count' => $item->count,
                    'percentage' => $summary->gross_revenue > 0
                        ? round(($item->amount / $summary->gross_revenue) * 100, 2)
                        : 0,
                ])
                ->toArray();

            // Get daily revenue for chart (limit to last 30 days for performance)
            $chartDays = min(30, $startDate->diffInDays($endDate) + 1);
            $chartStartDate = $endDate->copy()->subDays($chartDays - 1);

            $dailyRevenue = $bookingQuery->clone()
                ->selectRaw('DATE(bookings.created_at) as date')
                ->selectRaw('SUM(bookings.total_amount) as gross')
                ->selectRaw('COUNT(bookings.id) as bookings')
                ->whereBetween('bookings.created_at', [$chartStartDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            // Build chart data with all dates
            $chartData = [];
            for ($date = $chartStartDate->copy(); $date <= $endDate; $date->addDay()) {
                $dateStr = $date->format('Y-m-d');
                $dayData = $dailyRevenue->get($dateStr);
                $chartData[] = [
                    'date' => $date->format('M d'),
                    'gross' => $dayData ? round($dayData->gross, 2) : 0,
                    'bookings' => $dayData ? $dayData->bookings : 0,
                ];
            }

            // Get event-level breakdown if no specific event selected
            $eventBreakdown = [];
            if (! $this->event_id) {
                $eventBreakdown = $bookingQuery->clone()
                    ->select('events.id', 'events.title', 'events.commission_rate')
                    ->selectRaw('COUNT(DISTINCT bookings.id) as bookings')
                    ->selectRaw('SUM(bookings.total_amount) as revenue')
                    ->selectRaw('SUM(bookings.quantity) as tickets')
                    ->groupBy('events.id', 'events.title', 'events.commission_rate')
                    ->orderByDesc('revenue')
                    ->limit(10)
                    ->get()
                    ->map(fn ($event) => [
                        'id' => $event->id,
                        'title' => $event->title,
                        'bookings' => $event->bookings,
                        'tickets' => $event->tickets,
                        'revenue' => round($event->revenue, 2),
                        'commission' => round($event->revenue * ($event->commission_rate / 100), 2),
                        'net' => round($event->revenue * (1 - $event->commission_rate / 100), 2),
                    ])
                    ->toArray();
            }

            // Calculate final numbers
            $grossRevenue = $summary->gross_revenue ?? 0;
            $totalFees = ($transactionStats->total_paystack_fees ?? 0) +
                        ($transactionStats->total_commission ?? 0) +
                        ($transactionStats->total_processing_fees ?? 0);
            $netRevenue = $grossRevenue - $totalFees;

            return [
                'summary' => [
                    'total_bookings' => $summary->total_bookings ?? 0,
                    'total_tickets' => $summary->total_tickets ?? 0,
                    'gross_revenue' => round($grossRevenue, 2),
                    'net_revenue' => round($netRevenue, 2),
                    'avg_booking_value' => round($summary->avg_booking_value ?? 0, 2),
                    'total_paystack_fees' => round($transactionStats->total_paystack_fees ?? 0, 2),
                    'total_commission' => round($transactionStats->total_commission ?? 0, 2),
                    'total_processing_fees' => round($transactionStats->total_processing_fees ?? 0, 2),
                    'total_fees' => round($totalFees, 2),
                ],
                'revenue_by_method' => $revenueByMethod,
                'chart_data' => $chartData,
                'event_breakdown' => $eventBreakdown,
                'currency' => Auth::user()->organizer->default_currency ?? 'KES',
                'period_label' => $this->getPeriodLabel(),
            ];
        });
    }

    private function getPeriodLabel(): string
    {
        return match ($this->period) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => Carbon::now()->format('F Y'),
            'last_month' => Carbon::now()->subMonth()->format('F Y'),
            'this_year' => Carbon::now()->format('Y'),
            'custom' => Carbon::parse($this->date_from)->format('M d').' - '.Carbon::parse($this->date_to)->format('M d, Y'),
            default => 'All Time'
        };
    }

    private function clearRevenueCache(): void
    {
        $organizerId = Auth::user()->organizer?->id;
        if ($organizerId) {
            Cache::forget("revenue_data.{$organizerId}.{$this->period}.{$this->event_id}.{$this->date_from}.{$this->date_to}");
        }
    }

    private function getEmptyData(): array
    {
        return [
            'summary' => [
                'total_bookings' => 0,
                'total_tickets' => 0,
                'gross_revenue' => 0,
                'net_revenue' => 0,
                'avg_booking_value' => 0,
                'total_paystack_fees' => 0,
                'total_commission' => 0,
                'total_processing_fees' => 0,
                'total_fees' => 0,
            ],
            'revenue_by_method' => [],
            'chart_data' => [],
            'event_breakdown' => [],
            'currency' => 'KES',
            'period_label' => 'No Data',
        ];
    }
}
