<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class FinancialCalculationService
{
    /**
     * Payment gateway fee rates
     */
    const GATEWAY_FEES = [
        'paystack' => [
            'mpesa' => 0.015,    // 1.5% for M-Pesa
            'card' => 0.029,     // 2.9% for cards
            'apple_pay' => 0.029, // 2.9% for Apple Pay
            'bank_transfer' => 0.015, // 1.5% for bank transfer
        ],
        'mpesa_direct' => 0.01,  // 1% for direct M-Pesa (if implemented)
    ];

    /**
     * Calculate platform commission for a booking
     * Uses organizer's commission_rate set by admin
     */
    public function calculatePlatformCommission(Booking $booking): array
    {
        $event = $booking->event;
        $subtotal = $booking->subtotal;

        // Determine commission source and rate
        $commissionSource = 'default';
        $commissionRate = 10.0; // Default 10%
        $commissionType = 'percentage';

        // Use organizer's commission_rate (set by admin)
        // This rate applies to ALL events under this organizer
        if ($event->organizer && $event->organizer->commission_rate !== null) {
            $commissionRate = $event->organizer->commission_rate;
            $commissionType = 'percentage';
            $commissionSource = 'organizer_commission';
        }

        // Calculate commission amount (always percentage-based)
        $commissionAmount = round($subtotal * ($commissionRate / 100), 2);

        Log::info('Platform commission calculated', [
            'booking_id' => $booking->id,
            'event_id' => $event->id,
            'subtotal' => $subtotal,
            'commission_source' => $commissionSource,
            'commission_type' => $commissionType,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
        ]);

        return [
            'amount' => $commissionAmount,
            'rate' => $commissionRate,
            'type' => $commissionType,
            'source' => $commissionSource,
        ];
    }

    /**
     * Calculate gateway processing fee
     */
    public function calculateGatewayFee(float $amount, string $paymentGateway, string $paymentMethod): float
    {
        $gateway = strtolower($paymentGateway);
        $method = strtolower($paymentMethod);

        // Get the fee rate
        $feeRate = 0;

        if (isset(self::GATEWAY_FEES[$gateway])) {
            if (is_array(self::GATEWAY_FEES[$gateway])) {
                $feeRate = self::GATEWAY_FEES[$gateway][$method] ?? 0.015; // Default to 1.5%
            } else {
                $feeRate = self::GATEWAY_FEES[$gateway];
            }
        }

        return round($amount * $feeRate, 2);
    }

    /**
     * Calculate organizer's net amount from a booking
     * Net Amount = Total Amount - Gateway Fee - Platform Commission
     */
    public function calculateOrganizerNetAmount(Booking $booking, string $paymentGateway, string $paymentMethod): array
    {
        $totalAmount = $booking->total_amount;

        // Calculate gateway fee
        $gatewayFee = $this->calculateGatewayFee($totalAmount, $paymentGateway, $paymentMethod);

        // Calculate platform commission
        $commission = $this->calculatePlatformCommission($booking);

        // Calculate net amount for organizer
        // Net = Total - Gateway Fee - Commission
        $netAmount = $totalAmount - $gatewayFee - $commission['amount'];

        return [
            'gross_amount' => $totalAmount,
            'gateway_fee' => $gatewayFee,
            'platform_commission' => $commission['amount'],
            'commission_rate' => $commission['rate'],
            'commission_type' => $commission['type'],
            'commission_source' => $commission['source'],
            'net_amount' => max(0, $netAmount), // Ensure non-negative
        ];
    }

    /**
     * Create a comprehensive financial transaction record
     */
    public function createFinancialTransaction(
        Booking $booking,
        string $paymentGateway,
        string $paymentMethod,
        array $metadata = []
    ): Transaction {
        // Calculate all financial components
        $financials = $this->calculateOrganizerNetAmount($booking, $paymentGateway, $paymentMethod);

        // Create transaction with complete financial tracking
        $transaction = Transaction::create([
            'type' => Transaction::TYPE_TICKET_SALE,
            'booking_id' => $booking->id,
            'organizer_id' => $booking->event->organizer_id,
            'user_id' => $booking->user_id,
            'amount' => $financials['gross_amount'],
            'currency' => $booking->currency,

            // Commission tracking
            'commission_amount' => $financials['platform_commission'],
            'platform_commission' => $financials['platform_commission'], // Duplicate for clarity

            // Fee tracking
            'payment_processing_fee' => $financials['gateway_fee'],
            'paystack_fee' => $financials['gateway_fee'],

            // Net amount for organizer
            'net_amount' => $financials['net_amount'],

            // Payment details
            'payment_gateway' => $paymentGateway,
            'payment_method' => $paymentMethod,
            'payment_reference' => $booking->booking_reference,
            'status' => Transaction::STATUS_PENDING,

            // Store financial breakdown in metadata
            'metadata' => array_merge($metadata, [
                'financial_breakdown' => $financials,
                'event_id' => $booking->event_id,
                'event_title' => $booking->event->title,
                'ticket_quantity' => $booking->ticket_quantity,
                'commission_source' => $financials['commission_source'],
            ]),
        ]);

        Log::info('Financial transaction created', [
            'transaction_id' => $transaction->id,
            'booking_id' => $booking->id,
            'gross' => $financials['gross_amount'],
            'commission' => $financials['platform_commission'],
            'gateway_fee' => $financials['gateway_fee'],
            'net' => $financials['net_amount'],
        ]);

        return $transaction;
    }

    /**
     * Calculate refund amounts with proper commission and fee handling
     */
    public function calculateRefundAmounts(Transaction $originalTransaction, float $refundAmount): array
    {
        if ($refundAmount > $originalTransaction->amount) {
            throw new \Exception('Refund amount exceeds original transaction amount');
        }

        // Calculate proportional amounts
        $refundRatio = $refundAmount / $originalTransaction->amount;

        // Commission is returned to organizer on refund
        $commissionRefund = round($originalTransaction->commission_amount * $refundRatio, 2);

        // Gateway fees are typically not refunded (they keep their fee)
        $gatewayFeeRefund = 0; // Gateway usually keeps their fee

        // Net refund amount (what organizer loses)
        // Organizer loses: refund amount minus the commission they get back
        $netRefund = $refundAmount - $commissionRefund;

        return [
            'refund_amount' => $refundAmount,
            'commission_refund' => $commissionRefund,
            'gateway_fee_refund' => $gatewayFeeRefund,
            'net_refund' => $netRefund,
            'is_partial' => $refundAmount < $originalTransaction->amount,
        ];
    }

    /**
     * Create refund transaction with proper financial tracking
     */
    public function createRefundTransaction(
        Transaction $originalTransaction,
        float $refundAmount,
        ?string $reason = null
    ): Transaction {
        $booking = $originalTransaction->booking;

        // Calculate refund breakdown
        $refundBreakdown = $this->calculateRefundAmounts($originalTransaction, $refundAmount);

        // Create refund transaction
        $refundTransaction = Transaction::create([
            'type' => Transaction::TYPE_REFUND,
            'booking_id' => $booking->id,
            'organizer_id' => $originalTransaction->organizer_id,
            'user_id' => $booking->user_id,

            // Negative amounts for refunds
            'amount' => -$refundBreakdown['refund_amount'],
            'currency' => $originalTransaction->currency,
            'commission_amount' => -$refundBreakdown['commission_refund'],
            'platform_commission' => -$refundBreakdown['commission_refund'],
            'payment_processing_fee' => -$refundBreakdown['gateway_fee_refund'],
            'paystack_fee' => -$refundBreakdown['gateway_fee_refund'],
            'net_amount' => -$refundBreakdown['net_refund'],

            // Payment details
            'payment_gateway' => $originalTransaction->payment_gateway,
            'payment_method' => $originalTransaction->payment_method,
            'payment_reference' => 'REFUND-'.$booking->booking_reference,
            'status' => Transaction::STATUS_PENDING,

            // Metadata
            'metadata' => [
                'original_transaction_id' => $originalTransaction->id,
                'refund_reason' => $reason,
                'refund_breakdown' => $refundBreakdown,
                'partial_refund' => $refundBreakdown['is_partial'],
            ],
        ]);

        Log::info('Refund transaction created', [
            'refund_id' => $refundTransaction->id,
            'original_id' => $originalTransaction->id,
            'refund_amount' => $refundAmount,
            'net_refund' => $refundBreakdown['net_refund'],
        ]);

        return $refundTransaction;
    }

    /**
     * Get financial summary for a booking
     */
    public function getBookingFinancialSummary(Booking $booking): array
    {
        $event = $booking->event;
        $commission = $this->calculatePlatformCommission($booking);

        // Get the actual payment method from transaction if exists
        $transaction = Transaction::where('booking_id', $booking->id)
            ->where('type', Transaction::TYPE_TICKET_SALE)
            ->first();

        $gatewayFee = 0;
        if ($transaction) {
            $gatewayFee = $transaction->payment_processing_fee ?? $transaction->paystack_fee ?? 0;
        }

        $netAmount = $booking->total_amount - $commission['amount'] - $gatewayFee;

        return [
            'booking_reference' => $booking->booking_reference,
            'currency' => $booking->currency,
            'ticket_quantity' => $booking->ticket_quantity,
            'subtotal' => $booking->subtotal,
            'service_fee' => $booking->service_fee,
            'total_amount' => $booking->total_amount,
            'commission' => [
                'amount' => $commission['amount'],
                'rate' => $commission['rate'],
                'type' => $commission['type'],
                'source' => $commission['source'],
            ],
            'gateway_fee' => $gatewayFee,
            'organizer_net' => $netAmount,
            'payment_status' => $booking->payment_status,
            'transaction_id' => $transaction?->id,
        ];
    }

    /**
     * Reconcile financial records for an organizer
     */
    public function reconcileOrganizerFinancials(Organizer $organizer, ?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $query = Transaction::where('organizer_id', $organizer->id)
            ->where('status', Transaction::STATUS_COMPLETED);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $transactions = $query->get();

        // Calculate totals
        $totalSales = $transactions->where('type', Transaction::TYPE_TICKET_SALE)->sum('amount');
        $totalRefunds = abs($transactions->where('type', Transaction::TYPE_REFUND)->sum('amount'));
        $totalCommission = $transactions->sum('commission_amount');
        $totalGatewayFees = $transactions->sum('payment_processing_fee');
        $totalNet = $transactions->sum('net_amount');

        // Get payouts
        $payoutsQuery = \App\Models\Payout::where('organizer_id', $organizer->id);

        if ($startDate) {
            $payoutsQuery->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $payoutsQuery->where('created_at', '<=', $endDate);
        }

        $payouts = $payoutsQuery->get();
        $totalPaidOut = $payouts->where('status', 'completed')->sum('net_amount');
        $pendingPayouts = $payouts->whereIn('status', ['pending', 'approved', 'processing'])->sum('net_amount');

        return [
            'period' => [
                'start' => $startDate?->format('Y-m-d'),
                'end' => $endDate?->format('Y-m-d'),
            ],
            'summary' => [
                'gross_sales' => round($totalSales, 2),
                'refunds' => round($totalRefunds, 2),
                'net_sales' => round($totalSales - $totalRefunds, 2),
                'platform_commission' => round(abs($totalCommission), 2),
                'gateway_fees' => round(abs($totalGatewayFees), 2),
                'organizer_net_revenue' => round($totalNet, 2),
                'total_paid_out' => round($totalPaidOut, 2),
                'pending_payouts' => round($pendingPayouts, 2),
                'available_balance' => round($totalNet - $totalPaidOut - $pendingPayouts, 2),
            ],
            'transaction_count' => $transactions->count(),
            'currency' => $organizer->default_currency ?? 'KES',
        ];
    }
}
