<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Organizer;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    /**
     * Gateway fee rates.
     */
    const PAYSTACK_MPESA_FEE_RATE = 0.015; // 1.5% for M-Pesa via Paystack

    const PAYSTACK_CARD_FEE_RATE = 0.029; // 2.9% for cards via Paystack

    /**
     * Create a ticket sale transaction.
     */
    public function createTicketSale(
        Booking $booking,
        string $paymentGateway,
        string $paymentMethod,
        array $metadata = []
    ): Transaction {
        return DB::transaction(function () use ($booking, $paymentGateway, $paymentMethod, $metadata) {
            $amount = $booking->total_amount;

            // Calculate Paystack processing fee (1.5% for M-Pesa, 2.9% for cards)
            $paystackFee = $this->calculatePaystackFee($amount, $paymentMethod);

            // Get commission rate from event or organizer
            $commissionRate = $this->getCommissionRate($booking);
            $commission = round($amount * $commissionRate, 2);

            // Net amount = amount - Paystack fee only (commission deducted during payout)
            $netAmount = $amount - $paystackFee;

            // Create main transaction
            $transaction = Transaction::create([
                'type' => Transaction::TYPE_TICKET_SALE,
                'booking_id' => $booking->id,
                'organizer_id' => $booking->event->organizer_id,
                'user_id' => $booking->user_id,
                'amount' => $amount,
                'currency' => $booking->currency,
                'commission_amount' => $commission,
                'payment_processing_fee' => $paystackFee,
                'paystack_fee' => $paystackFee,
                'platform_commission' => $commission,
                'net_amount' => $netAmount,
                'payment_gateway' => $paymentGateway,
                'payment_method' => $paymentMethod,
                'payment_reference' => $booking->booking_reference,
                'status' => Transaction::STATUS_PENDING,
                'metadata' => $metadata,
            ]);

            Log::info('Ticket sale transaction created', [
                'transaction_id' => $transaction->id,
                'booking_id' => $booking->id,
                'amount' => $amount,
                'gateway' => $paymentGateway,
            ]);

            return $transaction;
        });
    }

    /**
     * Process Paystack payment.
     */
    public function processPaystackPayment(
        Transaction $transaction,
        string $reference,
        array $paystackData
    ): Transaction {
        $metadata = $transaction->metadata ?? [];

        // Add Paystack specific data
        $metadata['paystack_reference'] = $reference;
        $metadata['card_last4'] = $paystackData['card_last4'] ?? null;
        $metadata['card_type'] = $paystackData['card_type'] ?? null;
        $metadata['bank'] = $paystackData['bank'] ?? null;
        $metadata['payment_method'] = $paystackData['channel'] ?? 'card';

        $transaction->update([
            'gateway_reference' => $reference,
            'metadata' => $metadata,
            'status' => Transaction::STATUS_COMPLETED,
            'processed_at' => now(),
        ]);

        // Update booking status
        if ($transaction->booking) {
            $transaction->booking->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
            ]);
        }

        // Update organizer revenue
        if ($transaction->organizer) {
            $transaction->organizer->addRevenue(
                $transaction->currency,
                $transaction->net_amount
            );
        }

        return $transaction;
    }

    /**
     * Process M-Pesa payment.
     */
    public function processMpesaPayment(
        Transaction $transaction,
        array $mpesaData
    ): Transaction {
        $metadata = $transaction->metadata ?? [];

        // Add M-Pesa specific data
        $metadata['mpesa_receipt_number'] = $mpesaData['MpesaReceiptNumber'] ?? null;
        $metadata['mpesa_phone_number'] = $mpesaData['PhoneNumber'] ?? null;
        $metadata['mpesa_transaction_date'] = $mpesaData['TransactionDate'] ?? null;
        $metadata['mpesa_result_code'] = $mpesaData['ResultCode'] ?? null;

        $transaction->update([
            'gateway_reference' => $mpesaData['MpesaReceiptNumber'] ?? null,
            'metadata' => $metadata,
            'status' => Transaction::STATUS_COMPLETED,
            'processed_at' => now(),
        ]);

        // Update booking status
        if ($transaction->booking) {
            $transaction->booking->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
            ]);
        }

        // Update organizer revenue
        if ($transaction->organizer) {
            $transaction->organizer->addRevenue(
                $transaction->currency,
                $transaction->net_amount
            );
        }

        return $transaction;
    }

    /**
     * Create refund transaction.
     */
    public function createRefund(
        Booking $booking,
        float $amount,
        ?string $reason = null
    ): Transaction {
        return DB::transaction(function () use ($booking, $amount, $reason) {
            // Find original transaction
            $originalTransaction = Transaction::where('booking_id', $booking->id)
                ->where('type', Transaction::TYPE_TICKET_SALE)
                ->where('status', Transaction::STATUS_COMPLETED)
                ->first();

            if (! $originalTransaction) {
                throw new \Exception('Original transaction not found');
            }

            if ($amount > $originalTransaction->amount) {
                throw new \Exception('Refund amount exceeds original transaction');
            }

            // Calculate proportional commission and fees to refund
            $refundRatio = $amount / $originalTransaction->amount;
            $commissionRefund = round($originalTransaction->commission_amount * $refundRatio, 2);
            $feeRefund = round($originalTransaction->gateway_fee * $refundRatio, 2);
            $netRefund = $amount - $commissionRefund - $feeRefund;

            // Create refund transaction
            $refund = Transaction::create([
                'type' => Transaction::TYPE_REFUND,
                'booking_id' => $booking->id,
                'organizer_id' => $originalTransaction->organizer_id,
                'user_id' => $booking->user_id,
                'amount' => -$amount, // Negative for refund
                'currency' => $originalTransaction->currency,
                'commission_amount' => -$commissionRefund,
                'gateway_fee' => -$feeRefund,
                'net_amount' => -$netRefund,
                'payment_gateway' => $originalTransaction->payment_gateway,
                'payment_method' => $originalTransaction->payment_method,
                'payment_reference' => 'REFUND-'.$booking->booking_reference,
                'status' => Transaction::STATUS_PENDING,
                'metadata' => [
                    'original_transaction_id' => $originalTransaction->id,
                    'refund_reason' => $reason,
                    'partial_refund' => $amount < $originalTransaction->amount,
                ],
            ]);

            // Update booking status if full refund
            if ($amount == $originalTransaction->amount) {
                $booking->update([
                    'status' => 'refunded',
                    'payment_status' => 'refunded',
                ]);
            }

            Log::info('Refund transaction created', [
                'refund_id' => $refund->id,
                'booking_id' => $booking->id,
                'amount' => $amount,
            ]);

            return $refund;
        });
    }

    /**
     * Calculate Paystack processing fee based on payment method.
     */
    private function calculatePaystackFee(float $amount, string $paymentMethod): float
    {
        $rate = match ($paymentMethod) {
            'mpesa' => self::PAYSTACK_MPESA_FEE_RATE,
            'card', 'apple' => self::PAYSTACK_CARD_FEE_RATE,
            default => self::PAYSTACK_MPESA_FEE_RATE,
        };

        return round($amount * $rate, 2);
    }

    /**
     * Get commission rate from event or organizer settings.
     */
    private function getCommissionRate(Booking $booking): float
    {
        // First check if event has a specific commission rate
        if ($booking->event && $booking->event->commission_rate !== null) {
            return $booking->event->commission_rate / 100; // Convert percentage to decimal
        }

        // Otherwise use organizer's default commission rate
        if ($booking->event && $booking->event->organizer && $booking->event->organizer->commission_rate !== null) {
            return $booking->event->organizer->commission_rate / 100;
        }

        // Default to 10% if nothing is set
        return 0.10;
    }

    /**
     * Calculate gateway fees (deprecated - use calculatePaystackFee).
     */
    private function calculateGatewayFee(float $amount, string $gateway): float
    {
        return $this->calculatePaystackFee($amount, 'mpesa');
    }

    /**
     * Get transaction summary for organizer.
     */
    public function getOrganizerSummary(Organizer $organizer): array
    {
        $transactions = Transaction::where('organizer_id', $organizer->id)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->get();

        $totalSales = $transactions
            ->where('type', Transaction::TYPE_TICKET_SALE)
            ->sum('amount');

        $totalRefunds = abs($transactions
            ->where('type', Transaction::TYPE_REFUND)
            ->sum('amount'));

        $totalCommission = $transactions
            ->whereIn('type', [Transaction::TYPE_TICKET_SALE, Transaction::TYPE_REFUND])
            ->sum('commission_amount');

        $totalFees = $transactions
            ->whereIn('type', [Transaction::TYPE_TICKET_SALE, Transaction::TYPE_REFUND])
            ->sum('gateway_fee');

        $netRevenue = $transactions
            ->whereIn('type', [Transaction::TYPE_TICKET_SALE, Transaction::TYPE_REFUND])
            ->sum('net_amount');

        $totalPayouts = abs($transactions
            ->where('type', Transaction::TYPE_PAYOUT)
            ->sum('amount'));

        $availableBalance = $netRevenue - $totalPayouts;

        return [
            'total_sales' => $totalSales,
            'total_refunds' => $totalRefunds,
            'total_commission' => abs($totalCommission),
            'total_fees' => abs($totalFees),
            'net_revenue' => $netRevenue,
            'total_payouts' => $totalPayouts,
            'available_balance' => $availableBalance,
            'transaction_count' => $transactions->count(),
        ];
    }
}
