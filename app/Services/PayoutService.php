<?php

namespace App\Services;

use App\Models\Organizer;
use App\Models\Payout;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutService
{
    /**
     * Generate payout for an organizer for a specific period.
     */
    public function generatePayout(
        Organizer $organizer,
        Carbon $periodStart,
        Carbon $periodEnd
    ): ?Payout {
        // Check if payout already exists for this period
        $existingPayout = Payout::where('organizer_id', $organizer->id)
            ->where('period_start', $periodStart->format('Y-m-d'))
            ->where('period_end', $periodEnd->format('Y-m-d'))
            ->first();
        
        if ($existingPayout) {
            Log::warning('Payout already exists for period', [
                'organizer_id' => $organizer->id,
                'period' => $periodStart->format('Y-m-d') . ' to ' . $periodEnd->format('Y-m-d'),
            ]);
            return null;
        }
        
        // Get all completed ticket sales for the period
        $transactions = Transaction::where('organizer_id', $organizer->id)
            ->where('type', Transaction::TYPE_TICKET_SALE)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereBetween('processed_at', [$periodStart, $periodEnd->endOfDay()])
            ->whereNotIn('id', function ($query) {
                // Exclude transactions already included in other payouts
                $query->select(DB::raw("jsonb_array_elements_text(transaction_ids)::uuid"))
                    ->from('payouts')
                    ->whereNotNull('transaction_ids');
            })
            ->get();
        
        if ($transactions->isEmpty()) {
            Log::info('No transactions found for payout', [
                'organizer_id' => $organizer->id,
                'period' => $periodStart->format('Y-m-d') . ' to ' . $periodEnd->format('Y-m-d'),
            ]);
            return null;
        }
        
        // Calculate totals
        $grossAmount = $transactions->sum('amount');
        $commissionDeducted = $transactions->sum('commission_amount');
        $feesDeducted = $transactions->sum('gateway_fee');
        $netAmount = $transactions->sum('net_amount');
        
        // Account for refunds in the period
        $refunds = Transaction::where('organizer_id', $organizer->id)
            ->where('type', Transaction::TYPE_REFUND)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereBetween('processed_at', [$periodStart, $periodEnd->endOfDay()])
            ->get();
        
        if ($refunds->isNotEmpty()) {
            $refundAmount = abs($refunds->sum('amount'));
            $refundCommission = abs($refunds->sum('commission_amount'));
            $refundFees = abs($refunds->sum('gateway_fee'));
            $refundNet = abs($refunds->sum('net_amount'));
            
            $grossAmount -= $refundAmount;
            $commissionDeducted -= $refundCommission;
            $feesDeducted -= $refundFees;
            $netAmount -= $refundNet;
        }
        
        // Don't create payout if net amount is negative or zero
        if ($netAmount <= 0) {
            Log::info('Net amount is zero or negative, skipping payout', [
                'organizer_id' => $organizer->id,
                'net_amount' => $netAmount,
            ]);
            return null;
        }
        
        // Create payout record
        $payout = Payout::create([
            'organizer_id' => $organizer->id,
            'gross_amount' => $grossAmount,
            'commission_deducted' => $commissionDeducted,
            'fees_deducted' => $feesDeducted,
            'net_amount' => $netAmount,
            'currency' => 'KES', // Default currency
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'transaction_count' => $transactions->count() + $refunds->count(),
            'transaction_ids' => array_merge(
                $transactions->pluck('id')->toArray(),
                $refunds->pluck('id')->toArray()
            ),
            'status' => Payout::STATUS_PENDING,
        ]);
        
        Log::info('Payout generated successfully', [
            'payout_id' => $payout->id,
            'organizer_id' => $organizer->id,
            'net_amount' => $netAmount,
            'transaction_count' => $payout->transaction_count,
        ]);
        
        return $payout;
    }
    
    /**
     * Generate payouts for all organizers for a period.
     */
    public function generatePayoutsForAllOrganizers(
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        $results = [
            'created' => 0,
            'skipped' => 0,
            'errors' => 0,
            'payouts' => [],
        ];
        
        $organizers = Organizer::where('is_active', true)->get();
        
        foreach ($organizers as $organizer) {
            try {
                $payout = $this->generatePayout($organizer, $periodStart, $periodEnd);
                
                if ($payout) {
                    $results['created']++;
                    $results['payouts'][] = $payout;
                } else {
                    $results['skipped']++;
                }
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Error generating payout for organizer', [
                    'organizer_id' => $organizer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        Log::info('Batch payout generation completed', $results);
        
        return $results;
    }
    
    /**
     * Generate weekly payouts (for the previous week).
     */
    public function generateWeeklyPayouts(): array
    {
        $periodEnd = Carbon::now()->startOfWeek()->subDay(); // Last Sunday
        $periodStart = $periodEnd->copy()->subWeek()->addDay(); // Previous Monday
        
        return $this->generatePayoutsForAllOrganizers($periodStart, $periodEnd);
    }
    
    /**
     * Generate monthly payouts (for the previous month).
     */
    public function generateMonthlyPayouts(): array
    {
        $periodEnd = Carbon::now()->startOfMonth()->subDay(); // Last day of previous month
        $periodStart = $periodEnd->copy()->startOfMonth(); // First day of previous month
        
        return $this->generatePayoutsForAllOrganizers($periodStart, $periodEnd);
    }
    
    /**
     * Process approved payouts (actually send the money).
     */
    public function processApprovedPayouts(): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
        ];
        
        $payouts = Payout::where('status', Payout::STATUS_APPROVED)->get();
        
        foreach ($payouts as $payout) {
            try {
                // Mark as processing
                $payout->markAsProcessing(auth()->user());
                
                // TODO: Integrate with actual payment gateway
                // For now, we'll simulate success
                $paymentReference = 'PAY-' . strtoupper(uniqid());
                
                // Mark as paid
                $payout->markAsPaid($paymentReference);
                
                $results['processed']++;
                
                Log::info('Payout processed successfully', [
                    'payout_id' => $payout->id,
                    'payment_reference' => $paymentReference,
                ]);
            } catch (\Exception $e) {
                $payout->markAsFailed($e->getMessage());
                $results['failed']++;
                
                Log::error('Failed to process payout', [
                    'payout_id' => $payout->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $results;
    }
    
    /**
     * Get payout summary for an organizer.
     */
    public function getOrganizerPayoutSummary(Organizer $organizer): array
    {
        $payouts = Payout::where('organizer_id', $organizer->id)->get();
        
        return [
            'total_payouts' => $payouts->count(),
            'total_paid' => $payouts->where('status', Payout::STATUS_PAID)->sum('net_amount'),
            'pending_amount' => $payouts->where('status', Payout::STATUS_PENDING)->sum('net_amount'),
            'approved_amount' => $payouts->where('status', Payout::STATUS_APPROVED)->sum('net_amount'),
            'last_payout' => $payouts->where('status', Payout::STATUS_PAID)->sortByDesc('paid_at')->first(),
            'next_payout' => $payouts->whereIn('status', [Payout::STATUS_PENDING, Payout::STATUS_APPROVED])->first(),
        ];
    }
}