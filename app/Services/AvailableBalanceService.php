<?php

namespace App\Services;

use App\Models\Organizer;
use App\Models\Payout;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AvailableBalanceService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get organizer's available balance for payout
     */
    public function getAvailableBalance(Organizer $organizer): array
    {
        $cacheKey = "organizer_balance_{$organizer->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($organizer) {
            // Get total revenue from completed transactions
            $totalRevenue = $this->calculateTotalRevenue($organizer);

            // Get total refunds processed
            $totalRefunds = $this->calculateTotalRefunds($organizer);

            // Get total commission that will be deducted
            $totalCommission = $this->calculateTotalCommission($organizer);

            // Get already paid out amount
            $totalPaidOut = $this->calculateTotalPaidOut($organizer);

            // Get pending payout requests
            $pendingPayouts = $this->calculatePendingPayouts($organizer);

            // Get amount on hold (disputed transactions)
            $amountOnHold = $this->calculateAmountOnHold($organizer);

            // Calculate available balance
            $grossRevenue = $totalRevenue - $totalRefunds;
            $netRevenue = $grossRevenue - $totalCommission;
            $availableBalance = $netRevenue - $totalPaidOut - $pendingPayouts - $amountOnHold;

            return [
                'gross_revenue' => round($totalRevenue, 2),
                'total_refunds' => round($totalRefunds, 2),
                'adjusted_revenue' => round($grossRevenue, 2),
                'total_commission' => round($totalCommission, 2),
                'net_revenue' => round($netRevenue, 2),
                'total_paid_out' => round($totalPaidOut, 2),
                'pending_payouts' => round($pendingPayouts, 2),
                'amount_on_hold' => round($amountOnHold, 2),
                'available_balance' => round(max(0, $availableBalance), 2),
                'currency' => $organizer->fresh()->default_currency ?? 'KES', // Always get fresh currency value
                'last_updated' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Calculate total revenue from completed transactions
     */
    private function calculateTotalRevenue(Organizer $organizer): float
    {
        return Transaction::where('organizer_id', $organizer->id)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('type', Transaction::TYPE_TICKET_SALE)
            ->sum('net_amount');
    }

    /**
     * Calculate total refunds processed
     */
    private function calculateTotalRefunds(Organizer $organizer): float
    {
        return Transaction::where('organizer_id', $organizer->id)
            ->where('type', Transaction::TYPE_REFUND)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->sum(DB::raw('ABS(net_amount)'));
    }

    /**
     * Calculate total commission based on events and transactions
     */
    private function calculateTotalCommission(Organizer $organizer): float
    {
        // Get commission from ticket sales
        // Priority: platform_fee > commission_rate > organizer commission
        $salesCommission = DB::table('transactions')
            ->join('bookings', 'transactions.booking_id', '=', 'bookings.id')
            ->join('events', 'bookings.event_id', '=', 'events.id')
            ->join('organizers', 'events.organizer_id', '=', 'organizers.id')
            ->where('transactions.organizer_id', $organizer->id)
            ->where('transactions.status', Transaction::STATUS_COMPLETED)
            ->where('transactions.type', Transaction::TYPE_TICKET_SALE)
            ->selectRaw('
                SUM(
                    CASE 
                        -- Priority 1: Event platform_fee (percentage only)
                        WHEN events.platform_fee IS NOT NULL AND events.platform_fee > 0
                        THEN transactions.amount * (events.platform_fee / 100)
                        
                        -- Priority 2: Event commission_rate with commission_type
                        WHEN events.commission_rate IS NOT NULL
                        THEN 
                            CASE 
                                WHEN events.commission_type = \'fixed\' 
                                THEN events.commission_rate
                                ELSE transactions.amount * (events.commission_rate / 100)
                            END
                        
                        -- Priority 3: Organizer commission_rate (percentage)
                        WHEN organizers.commission_rate IS NOT NULL
                        THEN transactions.amount * (organizers.commission_rate / 100)
                        
                        -- Default 10%
                        ELSE transactions.amount * 0.10
                    END
                ) as total_commission
            ')
            ->value('total_commission') ?? 0;

        // Subtract commission for refunds (commission is returned on refunds)
        $refundCommission = DB::table('transactions')
            ->join('bookings', 'transactions.booking_id', '=', 'bookings.id')
            ->join('events', 'bookings.event_id', '=', 'events.id')
            ->join('organizers', 'events.organizer_id', '=', 'organizers.id')
            ->where('transactions.organizer_id', $organizer->id)
            ->where('transactions.status', Transaction::STATUS_COMPLETED)
            ->where('transactions.type', Transaction::TYPE_REFUND)
            ->selectRaw('
                SUM(
                    ABS(transactions.commission_amount)
                ) as refund_commission
            ')
            ->value('refund_commission') ?? 0;

        return $salesCommission - $refundCommission;
    }

    /**
     * Calculate total amount already paid out
     */
    private function calculateTotalPaidOut(Organizer $organizer): float
    {
        return Payout::where('organizer_id', $organizer->id)
            ->whereIn('status', ['completed', 'paid'])
            ->sum('net_amount');
    }

    /**
     * Calculate pending payout requests
     */
    private function calculatePendingPayouts(Organizer $organizer): float
    {
        return Payout::where('organizer_id', $organizer->id)
            ->whereIn('status', ['pending', 'approved', 'processing'])
            ->sum('net_amount');
    }

    /**
     * Calculate amount on hold due to disputes
     */
    private function calculateAmountOnHold(Organizer $organizer): float
    {
        return Transaction::where('organizer_id', $organizer->id)
            ->where('status', 'disputed')
            ->sum('net_amount');
    }

    /**
     * Get breakdown by event
     */
    public function getEventBreakdown(Organizer $organizer): array
    {
        $cacheKey = "organizer_event_breakdown_{$organizer->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($organizer) {
            return DB::table('transactions')
                ->join('bookings', 'transactions.booking_id', '=', 'bookings.id')
                ->join('events', 'bookings.event_id', '=', 'events.id')
                ->where('transactions.organizer_id', $organizer->id)
                ->where('transactions.status', Transaction::STATUS_COMPLETED)
                ->where('transactions.type', Transaction::TYPE_TICKET_SALE)
                ->groupBy('events.id', 'events.title', 'events.commission_rate', 'events.commission_type')
                ->selectRaw('
                    events.id,
                    events.title,
                    events.commission_rate,
                    events.commission_type,
                    COUNT(transactions.id) as transaction_count,
                    SUM(transactions.net_amount) as total_revenue,
                    SUM(
                        CASE 
                            WHEN events.commission_type = \'fixed\' 
                            THEN events.commission_rate
                            ELSE transactions.net_amount * (events.commission_rate / 100)
                        END
                    ) as total_commission
                ')
                ->orderByDesc('total_revenue')
                ->get()
                ->map(function ($event) {
                    return [
                        'event_id' => $event->id,
                        'title' => $event->title,
                        'commission_rate' => $event->commission_rate,
                        'commission_type' => $event->commission_type,
                        'transaction_count' => $event->transaction_count,
                        'total_revenue' => round($event->total_revenue, 2),
                        'total_commission' => round($event->total_commission, 2),
                        'net_revenue' => round($event->total_revenue - $event->total_commission, 2),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get payout fee based on method and amount
     */
    public function getPayoutFee(string $method, float $amount, Organizer $organizer): float
    {
        // Premium organizers may have fees absorbed
        if ($organizer->status === 'premium' && $organizer->absorb_payout_fees) {
            return 0;
        }

        // Standard fees
        return match ($method) {
            'mpesa' => 40, // KES 40 for M-Pesa
            'bank' => 80,  // KES 80 for bank transfer
            default => 0,
        };
    }

    /**
     * Validate payout request
     */
    public function validatePayoutRequest(Organizer $organizer, float $amount, string $method): array
    {
        $balance = $this->getAvailableBalance($organizer);
        $errors = [];

        // No minimum threshold - any positive amount is valid
        if ($amount <= 0) {
            $errors[] = "Payout amount must be greater than zero";
        }

        // Check available balance
        if ($amount > $balance['available_balance']) {
            $errors[] = "Insufficient balance. Available: {$balance['currency']} ".number_format($balance['available_balance'], 2);
        }

        // Check for existing pending payouts
        $hasPending = Payout::where('organizer_id', $organizer->id)
            ->whereIn('status', ['pending', 'approved', 'processing'])
            ->exists();

        if ($hasPending) {
            $errors[] = 'You have a pending payout request. Please wait for it to be processed.';
        }

        // Validate payout method details exist
        if ($method === 'mpesa' && empty($organizer->mpesa_number)) {
            $errors[] = 'Please add your M-Pesa number in settings before requesting payout.';
        }

        if ($method === 'bank' && (empty($organizer->bank_name) || empty($organizer->bank_account_number))) {
            $errors[] = 'Please add your bank details in settings before requesting payout.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'balance' => $balance,
        ];
    }

    /**
     * Clear cache for organizer
     */
    public function clearCache(Organizer $organizer): void
    {
        Cache::forget("organizer_balance_{$organizer->id}");
        Cache::forget("organizer_event_breakdown_{$organizer->id}");
    }
}
