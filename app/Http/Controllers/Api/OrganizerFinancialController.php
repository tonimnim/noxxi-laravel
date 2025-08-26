<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organizer;
use App\Models\Transaction;
use App\Services\AvailableBalanceService;
use App\Services\FinancialCalculationService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrganizerFinancialController extends Controller
{
    use ApiResponse;

    protected FinancialCalculationService $financialService;

    protected AvailableBalanceService $balanceService;

    public function __construct(
        FinancialCalculationService $financialService,
        AvailableBalanceService $balanceService
    ) {
        $this->financialService = $financialService;
        $this->balanceService = $balanceService;
    }

    /**
     * Get organizer's financial summary
     *
     * @authenticated
     *
     * @group Financials
     */
    public function summary(Request $request)
    {
        $user = Auth::user();

        if (! $user->organizer) {
            return $this->forbidden('You must be an organizer to access financial data');
        }

        $organizer = $user->organizer;

        // Get date range from request
        $startDate = $request->input('start_date') ?
            \DateTime::createFromFormat('Y-m-d', $request->input('start_date')) : null;
        $endDate = $request->input('end_date') ?
            \DateTime::createFromFormat('Y-m-d', $request->input('end_date')) : null;

        // Get financial reconciliation
        $reconciliation = $this->financialService->reconcileOrganizerFinancials(
            $organizer,
            $startDate,
            $endDate
        );

        // Get available balance
        $balance = $this->balanceService->getAvailableBalance($organizer);

        return $this->success([
            'reconciliation' => $reconciliation,
            'current_balance' => $balance,
        ]);
    }

    /**
     * Get detailed transaction history
     *
     * @authenticated
     *
     * @group Financials
     */
    public function transactions(Request $request)
    {
        $user = Auth::user();

        if (! $user->organizer) {
            return $this->forbidden('You must be an organizer to access financial data');
        }

        $organizer = $user->organizer;

        $query = Transaction::query()
            ->select([
                'transactions.id',
                'transactions.type',
                'transactions.booking_id',
                'transactions.amount',
                'transactions.currency',
                'transactions.commission_amount',
                'transactions.gateway_fee',
                'transactions.net_amount',
                'transactions.payment_gateway',
                'transactions.payment_method',
                'transactions.payment_reference',
                'transactions.status',
                'transactions.created_at',
                'transactions.processed_at',
            ])
            ->where('transactions.organizer_id', $organizer->id)
            ->with([
                'booking:id,booking_reference,event_id,customer_name,ticket_quantity',
                'booking.event:id,title,event_date',
            ])
            ->orderBy('transactions.created_at', 'desc');

        // Apply filters
        if ($request->filled('type')) {
            $query->where('transactions.type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('transactions.status', $request->input('status'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('transactions.created_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('transactions.created_at', '<=', $request->input('end_date'));
        }

        $transactions = $query->paginate($request->input('per_page', 20));

        // Transform the data
        $items = $transactions->getCollection()->map(function ($transaction) {
            $data = [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'commission' => [
                    'amount' => abs($transaction->commission_amount),
                    'rate' => $this->calculateCommissionRate($transaction),
                ],
                'gateway_fee' => abs($transaction->gateway_fee),
                'net_amount' => $transaction->net_amount,
                'payment_method' => $transaction->payment_method,
                'reference' => $transaction->payment_reference,
                'status' => $transaction->status,
                'created_at' => $transaction->created_at->toIso8601String(),
                'processed_at' => $transaction->processed_at?->toIso8601String(),
            ];

            if ($transaction->booking) {
                $data['booking'] = [
                    'reference' => $transaction->booking->booking_reference,
                    'customer' => $transaction->booking->customer_name,
                    'tickets' => $transaction->booking->ticket_quantity,
                ];

                if ($transaction->booking->event) {
                    $data['event'] = [
                        'id' => $transaction->booking->event->id,
                        'title' => $transaction->booking->event->title,
                        'date' => $transaction->booking->event->event_date->toIso8601String(),
                    ];
                }
            }

            return $data;
        });

        return $this->success([
            'items' => $items,
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Get commission breakdown by event
     *
     * @authenticated
     *
     * @group Financials
     */
    public function commissionBreakdown(Request $request)
    {
        $user = Auth::user();

        if (! $user->organizer) {
            return $this->forbidden('You must be an organizer to access financial data');
        }

        $organizer = $user->organizer;

        // Get commission breakdown by event
        $breakdown = $this->balanceService->getEventBreakdown($organizer);

        // Get commission statistics
        $stats = DB::table('transactions')
            ->join('bookings', 'transactions.booking_id', '=', 'bookings.id')
            ->join('events', 'bookings.event_id', '=', 'events.id')
            ->where('transactions.organizer_id', $organizer->id)
            ->where('transactions.status', Transaction::STATUS_COMPLETED)
            ->where('transactions.type', Transaction::TYPE_TICKET_SALE)
            ->selectRaw('
                COUNT(DISTINCT events.id) as total_events,
                COUNT(transactions.id) as total_transactions,
                SUM(transactions.amount) as gross_revenue,
                SUM(transactions.commission_amount) as total_commission,
                SUM(transactions.gateway_fee) as total_gateway_fees,
                SUM(transactions.net_amount) as net_revenue,
                AVG(transactions.commission_amount / NULLIF(transactions.amount, 0) * 100) as avg_commission_rate
            ')
            ->first();

        return $this->success([
            'by_event' => $breakdown,
            'statistics' => [
                'total_events' => $stats->total_events ?? 0,
                'total_transactions' => $stats->total_transactions ?? 0,
                'gross_revenue' => round($stats->gross_revenue ?? 0, 2),
                'total_commission' => round(abs($stats->total_commission ?? 0), 2),
                'total_gateway_fees' => round(abs($stats->total_gateway_fees ?? 0), 2),
                'net_revenue' => round($stats->net_revenue ?? 0, 2),
                'average_commission_rate' => round($stats->avg_commission_rate ?? 0, 2),
                'currency' => $organizer->default_currency ?? 'KES',
            ],
        ]);
    }

    /**
     * Calculate commission rate from transaction
     */
    private function calculateCommissionRate(Transaction $transaction): float
    {
        if ($transaction->amount == 0) {
            return 0;
        }

        return round((abs($transaction->commission_amount) / $transaction->amount) * 100, 2);
    }
}
