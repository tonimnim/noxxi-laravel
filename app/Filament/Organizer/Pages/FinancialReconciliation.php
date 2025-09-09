<?php

namespace App\Filament\Organizer\Pages;

use App\Models\Payout;
use App\Models\RefundRequest;
use App\Models\Transaction;
use App\Services\AvailableBalanceService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FinancialReconciliation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Financial Report';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 5;
    
    // Hide from navigation until implemented
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.organizer.pages.financial-reconciliation';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function mount(): void
    {
        // Default to current month
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function getReconciliationData(): array
    {
        $organizer = Auth::user()->organizer;

        if (! $organizer) {
            return $this->getEmptyData();
        }

        $cacheKey = "reconciliation_{$organizer->id}_{$this->dateFrom}_{$this->dateTo}";

        return Cache::remember($cacheKey, 300, function () use ($organizer) {
            $startDate = Carbon::parse($this->dateFrom)->startOfDay();
            $endDate = Carbon::parse($this->dateTo)->endOfDay();

            // Get balance summary from service
            $balanceService = app(AvailableBalanceService::class);
            $balanceData = $balanceService->getAvailableBalance($organizer);

            // Get ticket sales for period
            $ticketSales = Transaction::where('organizer_id', $organizer->id)
                ->where('type', Transaction::TYPE_TICKET_SALE)
                ->where('status', Transaction::STATUS_COMPLETED)
                ->whereBetween('processed_at', [$startDate, $endDate])
                ->select([
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(amount) as gross_amount'),
                    DB::raw('SUM(commission_amount) as commission'),
                    DB::raw('SUM(gateway_fee) as fees'),
                    DB::raw('SUM(net_amount) as net_amount'),
                ])
                ->first();

            // Get refunds for period
            $refunds = Transaction::where('organizer_id', $organizer->id)
                ->where('type', Transaction::TYPE_REFUND)
                ->where('status', Transaction::STATUS_COMPLETED)
                ->whereBetween('processed_at', [$startDate, $endDate])
                ->select([
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(ABS(amount)) as gross_amount'),
                    DB::raw('SUM(ABS(commission_amount)) as commission'),
                    DB::raw('SUM(ABS(gateway_fee)) as fees'),
                    DB::raw('SUM(ABS(net_amount)) as net_amount'),
                ])
                ->first();

            // Get refund requests breakdown
            $refundRequests = RefundRequest::query()
                ->whereHas('booking.event', function ($query) use ($organizer) {
                    $query->where('organizer_id', $organizer->id);
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select([
                    'status',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(requested_amount) as requested_amount'),
                    DB::raw('SUM(approved_amount) as approved_amount'),
                ])
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            // Get payouts for period
            $payouts = Payout::where('organizer_id', $organizer->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select([
                    'status',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(net_amount) as amount'),
                ])
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            // Calculate net position
            $periodRevenue = $ticketSales->net_amount ?? 0;
            $periodRefunds = $refunds->net_amount ?? 0;
            $periodNet = $periodRevenue - $periodRefunds;

            // Get event breakdown
            $eventBreakdown = DB::table('transactions')
                ->join('bookings', 'transactions.booking_id', '=', 'bookings.id')
                ->join('events', 'bookings.event_id', '=', 'events.id')
                ->where('transactions.organizer_id', $organizer->id)
                ->whereBetween('transactions.processed_at', [$startDate, $endDate])
                ->where('transactions.status', Transaction::STATUS_COMPLETED)
                ->groupBy('events.id', 'events.title', 'transactions.type')
                ->select([
                    'events.id',
                    'events.title',
                    'transactions.type',
                    DB::raw('COUNT(*) as transaction_count'),
                    DB::raw('SUM(
                        CASE 
                            WHEN transactions.type = \''.Transaction::TYPE_TICKET_SALE.'\' 
                            THEN transactions.net_amount 
                            ELSE -ABS(transactions.net_amount) 
                        END
                    ) as net_amount'),
                ])
                ->orderByDesc('net_amount')
                ->get()
                ->groupBy('title');

            return [
                'period' => [
                    'from' => $startDate->format('M d, Y'),
                    'to' => $endDate->format('M d, Y'),
                ],
                'summary' => [
                    'available_balance' => $balanceData['available_balance'],
                    'gross_revenue' => $balanceData['gross_revenue'],
                    'total_refunds' => $balanceData['total_refunds'],
                    'adjusted_revenue' => $balanceData['adjusted_revenue'],
                    'total_commission' => $balanceData['total_commission'],
                    'net_revenue' => $balanceData['net_revenue'],
                    'currency' => $balanceData['currency'],
                ],
                'period_data' => [
                    'ticket_sales' => [
                        'count' => $ticketSales->count ?? 0,
                        'gross' => $ticketSales->gross_amount ?? 0,
                        'commission' => $ticketSales->commission ?? 0,
                        'fees' => $ticketSales->fees ?? 0,
                        'net' => $ticketSales->net_amount ?? 0,
                    ],
                    'refunds' => [
                        'count' => $refunds->count ?? 0,
                        'gross' => $refunds->gross_amount ?? 0,
                        'commission' => $refunds->commission ?? 0,
                        'fees' => $refunds->fees ?? 0,
                        'net' => $refunds->net_amount ?? 0,
                    ],
                    'net_position' => $periodNet,
                ],
                'refund_requests' => [
                    'pending' => [
                        'count' => $refundRequests->get(RefundRequest::STATUS_PENDING)?->count ?? 0,
                        'amount' => $refundRequests->get(RefundRequest::STATUS_PENDING)?->requested_amount ?? 0,
                    ],
                    'approved' => [
                        'count' => $refundRequests->get(RefundRequest::STATUS_APPROVED)?->count ?? 0,
                        'amount' => $refundRequests->get(RefundRequest::STATUS_APPROVED)?->approved_amount ?? 0,
                    ],
                    'rejected' => [
                        'count' => $refundRequests->get(RefundRequest::STATUS_REJECTED)?->count ?? 0,
                        'amount' => $refundRequests->get(RefundRequest::STATUS_REJECTED)?->requested_amount ?? 0,
                    ],
                    'processed' => [
                        'count' => $refundRequests->get(RefundRequest::STATUS_PROCESSED)?->count ?? 0,
                        'amount' => $refundRequests->get(RefundRequest::STATUS_PROCESSED)?->approved_amount ?? 0,
                    ],
                ],
                'payouts' => [
                    'pending' => [
                        'count' => $payouts->get(Payout::STATUS_PENDING)?->count ?? 0,
                        'amount' => $payouts->get(Payout::STATUS_PENDING)?->amount ?? 0,
                    ],
                    'approved' => [
                        'count' => $payouts->get(Payout::STATUS_APPROVED)?->count ?? 0,
                        'amount' => $payouts->get(Payout::STATUS_APPROVED)?->amount ?? 0,
                    ],
                    'paid' => [
                        'count' => $payouts->get(Payout::STATUS_PAID)?->count ?? 0,
                        'amount' => $payouts->get(Payout::STATUS_PAID)?->amount ?? 0,
                    ],
                ],
                'event_breakdown' => $eventBreakdown->map(function ($transactions, $eventTitle) {
                    $sales = $transactions->firstWhere('type', Transaction::TYPE_TICKET_SALE);
                    $refunds = $transactions->firstWhere('type', Transaction::TYPE_REFUND);

                    return [
                        'title' => $eventTitle,
                        'sales_count' => $sales?->transaction_count ?? 0,
                        'sales_amount' => $sales?->net_amount ?? 0,
                        'refund_count' => $refunds?->transaction_count ?? 0,
                        'refund_amount' => abs($refunds?->net_amount ?? 0),
                        'net_amount' => ($sales?->net_amount ?? 0) + ($refunds?->net_amount ?? 0),
                    ];
                })->values()->toArray(),
            ];
        });
    }

    private function getEmptyData(): array
    {
        return [
            'period' => [
                'from' => Carbon::parse($this->dateFrom)->format('M d, Y'),
                'to' => Carbon::parse($this->dateTo)->format('M d, Y'),
            ],
            'summary' => [
                'available_balance' => 0,
                'gross_revenue' => 0,
                'total_refunds' => 0,
                'adjusted_revenue' => 0,
                'total_commission' => 0,
                'net_revenue' => 0,
                'currency' => 'KES',
            ],
            'period_data' => [
                'ticket_sales' => [
                    'count' => 0,
                    'gross' => 0,
                    'commission' => 0,
                    'fees' => 0,
                    'net' => 0,
                ],
                'refunds' => [
                    'count' => 0,
                    'gross' => 0,
                    'commission' => 0,
                    'fees' => 0,
                    'net' => 0,
                ],
                'net_position' => 0,
            ],
            'refund_requests' => [
                'pending' => ['count' => 0, 'amount' => 0],
                'approved' => ['count' => 0, 'amount' => 0],
                'rejected' => ['count' => 0, 'amount' => 0],
                'processed' => ['count' => 0, 'amount' => 0],
            ],
            'payouts' => [
                'pending' => ['count' => 0, 'amount' => 0],
                'approved' => ['count' => 0, 'amount' => 0],
                'paid' => ['count' => 0, 'amount' => 0],
            ],
            'event_breakdown' => [],
        ];
    }

    public function updateDateRange(): void
    {
        // Clear cache when date range changes
        $organizer = Auth::user()->organizer;
        if ($organizer) {
            $cacheKey = "reconciliation_{$organizer->id}_{$this->dateFrom}_{$this->dateTo}";
            Cache::forget($cacheKey);
        }

        // Refresh the page data
        $this->dispatch('refreshComponent');
    }
}
