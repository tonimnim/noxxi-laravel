<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PayoutReceiptController extends Controller
{
    /**
     * Generate and download payout receipt PDF
     */
    public function download(Request $request, string $payoutId)
    {
        $payout = Payout::where('id', $payoutId)
            ->where('organizer_id', auth()->user()->organizer->id)
            ->firstOrFail();

        // Only allow receipts for completed payouts
        if (! in_array($payout->status, ['completed', 'paid'])) {
            abort(403, 'Receipt not available for this payout status');
        }

        $data = $this->prepareReceiptData($payout);

        $pdf = Pdf::loadView('pdfs.payout-receipt', $data);

        $filename = 'payout-receipt-'.$payout->reference.'.pdf';

        return $pdf->download($filename);
    }

    /**
     * View payout receipt in browser
     */
    public function view(Request $request, string $payoutId)
    {
        $payout = Payout::where('id', $payoutId)
            ->where('organizer_id', auth()->user()->organizer->id)
            ->firstOrFail();

        // Only allow receipts for completed payouts
        if (! in_array($payout->status, ['completed', 'paid'])) {
            abort(403, 'Receipt not available for this payout status');
        }

        $data = $this->prepareReceiptData($payout);

        $pdf = Pdf::loadView('pdfs.payout-receipt', $data);

        return $pdf->stream('payout-receipt-'.$payout->reference.'.pdf');
    }

    /**
     * Prepare data for receipt
     */
    private function prepareReceiptData(Payout $payout): array
    {
        $organizer = $payout->organizer;
        $currency = $payout->currency ?? 'KES';

        // Get transaction breakdown
        $transactions = \App\Models\Transaction::with(['booking.event'])
            ->where('organizer_id', $organizer->id)
            ->whereBetween('created_at', [$payout->period_start, $payout->period_end])
            ->whereIn('status', ['successful', 'completed'])
            ->get();

        $transactionSummary = $transactions->groupBy(function ($transaction) {
            return $transaction->booking ? $transaction->booking->event_id : null;
        })->map(function ($group) {
            $firstTransaction = $group->first();
            $event = $firstTransaction->booking ? $firstTransaction->booking->event : null;

            return [
                'event_title' => $event ? $event->title : 'Unknown Event',
                'transaction_count' => $group->count(),
                'total_amount' => $group->sum('net_amount'),
                'commission' => $group->sum('commission_amount'),
            ];
        });

        return [
            'payout' => $payout,
            'organizer' => $organizer,
            'currency' => $currency,
            'issue_date' => now(),
            'transaction_summary' => $transactionSummary,
            'gross_amount' => $payout->gross_amount,
            'commission_amount' => $payout->commission_amount ?? $payout->commission_deducted,
            'payout_fee' => $payout->payout_fee ?? $payout->fees_deducted,
            'net_amount' => $payout->net_amount,
            'payment_method' => ucfirst($payout->payment_method),
            'payment_details' => $this->getPaymentDetails($payout),
            'company' => [
                'name' => 'Noxxi Platform',
                'address' => 'Nairobi, Kenya',
                'email' => 'payouts@noxxi.com',
                'phone' => '+254 700 000000',
                'website' => 'www.noxxi.com',
            ],
        ];
    }

    /**
     * Get masked payment details
     */
    private function getPaymentDetails(Payout $payout): string
    {
        if ($payout->payment_method === 'mpesa') {
            $number = $payout->payout_details['number'] ?? $payout->organizer->mpesa_number;
            if ($number) {
                return 'M-Pesa: '.substr($number, 0, 4).'****'.substr($number, -2);
            }
        } elseif ($payout->payment_method === 'bank') {
            $details = $payout->payout_details ?? [];
            $bankName = $details['bank_name'] ?? $payout->organizer->bank_name;
            $accountNumber = $details['account_number'] ?? $payout->organizer->bank_account_number;

            if ($bankName && $accountNumber) {
                return $bankName.' ****'.substr($accountNumber, -4);
            }
        }

        return 'N/A';
    }
}
