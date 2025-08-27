<?php

namespace App\Filament\Organizer\Widgets;

use App\Models\Booking;
use App\Models\Payout;
use App\Models\User;
use App\Services\AvailableBalanceService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PayoutsSummary extends Widget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected static string $view = 'filament.organizer.widgets.payouts-summary';
    
    protected static ?string $pollingInterval = '5s'; // Poll every 5 seconds for real-time balance updates

    public function getPayoutData(): array
    {
        $organizer = Auth::user()->organizer;

        if (! $organizer) {
            return [
                'available_balance' => 0,
                'pending_payouts' => 0,
                'total_refunds' => 0,
                'last_payout' => null,
                'recent_activity' => collect([]),
                'currency' => 'USD',
            ];
        }

        // Use the AvailableBalanceService which properly handles refunds
        $balanceService = app(AvailableBalanceService::class);
        $balanceData = $balanceService->getAvailableBalance($organizer);

        // Get pending payouts
        $pendingPayouts = Payout::where('organizer_id', $organizer->id)
            ->where('status', 'pending')
            ->sum('net_amount');

        // Get last payout
        $lastPayout = Payout::where('organizer_id', $organizer->id)
            ->where('status', 'completed')
            ->latest('paid_at')
            ->first();

        // Get recent bookings for activity feed (more efficient than transactions)
        $recentActivity = Booking::whereHas('event', function ($query) use ($organizer) {
            $query->where('organizer_id', $organizer->id);
        })
            ->with('event:id,title')
            ->whereIn('payment_status', ['paid', 'refunded'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($booking) {
                return [
                    'type' => $booking->payment_status === 'refunded' ? 'refund' : 'ticket_sale',
                    'amount' => $booking->subtotal, // Show organizer's portion
                    'currency' => $booking->currency,
                    'event' => $booking->event?->title ?? 'N/A',
                    'time' => $booking->created_at->diffForHumans(),
                    'status' => $booking->payment_status,
                ];
            });

        return [
            'available_balance' => $balanceData['available_balance'],
            'pending_payouts' => $pendingPayouts,
            'total_refunds' => $balanceData['total_refunds'],
            'gross_revenue' => $balanceData['gross_revenue'],
            'adjusted_revenue' => $balanceData['adjusted_revenue'],
            'last_payout' => $lastPayout ? [
                'amount' => $lastPayout->net_amount,
                'date' => $lastPayout->paid_at->format('M j, Y'),
            ] : null,
            'recent_activity' => $recentActivity,
            'currency' => $balanceData['currency'],
        ];
    }
    
    public function requestPayout(): void
    {
        $organizer = Auth::user()->organizer;
        
        if (!$organizer) {
            Notification::make()
                ->title('Error')
                ->body('No organizer account found.')
                ->danger()
                ->send();
            return;
        }
        
        // Get balance data
        $balanceService = app(AvailableBalanceService::class);
        $balanceData = $balanceService->getAvailableBalance($organizer);
        
        if ($balanceData['available_balance'] <= 0) {
            Notification::make()
                ->title('No Balance')
                ->body('You have no available balance to request payout.')
                ->warning()
                ->send();
            return;
        }
        
        // Check if there's already a pending payout
        $pendingPayout = Payout::where('organizer_id', $organizer->id)
            ->whereIn('status', ['pending', 'on_hold'])
            ->exists();
            
        if ($pendingPayout) {
            Notification::make()
                ->title('Existing Request')
                ->body('You already have a pending payout request. Please wait for it to be processed.')
                ->warning()
                ->send();
            return;
        }
        
        try {
            // Create the payout record
            $payout = Payout::create([
                'organizer_id' => $organizer->id,
                'requested_amount' => $balanceData['available_balance'],
                'gross_amount' => $balanceData['gross_revenue'] ?? $balanceData['available_balance'],
                'commission_deducted' => $balanceData['total_commission'] ?? 0,
                'platform_commission' => $balanceData['total_commission'] ?? 0,
                'fees_deducted' => 0, // Will be calculated when processing
                'payout_fee' => 0, // Will be calculated when processing
                'fee_absorbed' => false,
                'net_amount' => $balanceData['available_balance'], // This is already after commission
                'currency' => $balanceData['currency'],
                'type' => 'full', // Full payout
                'payout_method' => $organizer->payout_method ?? 'bank',
                'payout_details' => [
                    'bank_name' => $organizer->bank_name,
                    'bank_account' => $organizer->bank_account_number,
                    'mpesa_number' => $organizer->mpesa_number,
                ],
                'status' => 'pending',
                'booking_ids' => $balanceData['booking_ids'] ?? [],
                'transaction_ids' => $balanceData['booking_ids'] ?? [], // Using booking_ids as transaction_ids for now
                'transaction_count' => count($balanceData['booking_ids'] ?? []),
                'metadata' => [
                    'gross_revenue' => $balanceData['gross_revenue'],
                    'total_refunds' => $balanceData['total_refunds'],
                    'adjusted_revenue' => $balanceData['adjusted_revenue'],
                ],
                'requested_at' => now(),
                'period_start' => $balanceData['period_start'] ?? now()->subMonth(),
                'period_end' => $balanceData['period_end'] ?? now(),
            ]);
            
            // Send notification to all admins
            $admins = User::where('role', 'admin')->get();
            
            foreach ($admins as $admin) {
                Notification::make()
                    ->title('New Payout Request')
                    ->body($organizer->business_name . ' has requested a payout of ' . 
                           $balanceData['currency'] . ' ' . number_format($balanceData['available_balance'], 2))
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->label('View Payout')
                            ->url('/admin/payouts')
                            ->button(),
                    ])
                    ->sendToDatabase($admin);
            }
            
            // Log the request
            Log::info('Payout request created', [
                'payout_id' => $payout->id,
                'organizer_id' => $organizer->id,
                'business_name' => $organizer->business_name,
                'amount' => $balanceData['available_balance'],
                'currency' => $balanceData['currency'],
                'reference' => $payout->reference_number,
                'timestamp' => now()->toDateTimeString(),
            ]);
            
            Notification::make()
                ->title('Request Sent')
                ->body('Your payout request (Ref: ' . $payout->reference_number . ') has been sent to the administrators.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Failed to create payout request: ' . $e->getMessage());
            
            Notification::make()
                ->title('Request Failed')
                ->body('Failed to create payout request. Please try again later.')
                ->danger()
                ->send();
        }
    }
}
