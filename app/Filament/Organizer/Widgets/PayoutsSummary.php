<?php

namespace App\Filament\Organizer\Widgets;

use App\Models\Payout;
use App\Models\Booking;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PayoutsSummary extends Widget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 1;
    protected static string $view = 'filament.organizer.widgets.payouts-summary';
    
    public function getPayoutData(): array
    {
        $organizerId = Auth::user()->organizer?->id;
        
        if (!$organizerId) {
            return [
                'available_balance' => 0,
                'pending_payouts' => 0,
                'next_payout_date' => Carbon::now()->next(Carbon::FRIDAY)->format('M j, Y'),
                'last_payout' => null,
                'recent_activity' => collect([]),
                'currency' => 'KES',
            ];
        }
        
        // Calculate available balance from paid bookings
        $totalRevenue = Booking::whereHas('event', function ($query) use ($organizerId) {
                $query->where('organizer_id', $organizerId);
            })
            ->where('payment_status', 'paid')
            ->sum('total_amount');
            
        // Calculate platform commission (10% default)
        $commissionRate = 0.10;
        $netRevenue = $totalRevenue * (1 - $commissionRate);
        
        $totalPaidOut = Payout::where('organizer_id', $organizerId)
            ->whereIn('status', ['completed', 'processing'])
            ->sum('net_amount');
            
        $availableBalance = $netRevenue - $totalPaidOut;
        
        // Get pending payouts
        $pendingPayouts = Payout::where('organizer_id', $organizerId)
            ->where('status', 'pending')
            ->sum('net_amount');
            
        // Get last payout
        $lastPayout = Payout::where('organizer_id', $organizerId)
            ->where('status', 'completed')
            ->latest('paid_at')
            ->first();
            
        // Calculate next payout date (assuming weekly payouts)
        $nextPayoutDate = $lastPayout 
            ? Carbon::parse($lastPayout->paid_at)->addWeek()
            : Carbon::now()->next(Carbon::FRIDAY);
            
        // Get recent bookings for activity feed (more efficient than transactions)
        $recentActivity = Booking::whereHas('event', function ($query) use ($organizerId) {
                $query->where('organizer_id', $organizerId);
            })
            ->with('event:id,title')
            ->whereIn('payment_status', ['paid', 'refunded'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($booking) {
                return [
                    'type' => $booking->payment_status === 'refunded' ? 'refund' : 'ticket_sale',
                    'amount' => $booking->total_amount,
                    'currency' => $booking->currency,
                    'event' => $booking->event?->title ?? 'N/A',
                    'time' => $booking->created_at->diffForHumans(),
                    'status' => $booking->payment_status,
                ];
            });
        
        return [
            'available_balance' => $availableBalance,
            'pending_payouts' => $pendingPayouts,
            'next_payout_date' => $nextPayoutDate->format('M j, Y'),
            'last_payout' => $lastPayout ? [
                'amount' => $lastPayout->net_amount,
                'date' => $lastPayout->paid_at->format('M j, Y'),
            ] : null,
            'recent_activity' => $recentActivity,
            'currency' => 'KES',
        ];
    }
}