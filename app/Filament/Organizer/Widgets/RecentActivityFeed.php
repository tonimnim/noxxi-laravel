<?php

namespace App\Filament\Organizer\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Ticket;
use Carbon\Carbon;

class RecentActivityFeed extends Widget
{
    protected static string $view = 'filament.organizer.widgets.recent-activity-feed';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;
    
    public function getActivities()
    {
        $organizerId = Auth::user()->organizer?->id;
        
        if (!$organizerId) {
            return collect([]);
        }
        
        $activities = collect([]);
        
        // Get recent bookings
        $recentBookings = Booking::whereHas('event', function ($query) use ($organizerId) {
            $query->where('organizer_id', $organizerId);
        })
        ->with(['event', 'user'])
        ->where('created_at', '>=', Carbon::now()->subDays(7))
        ->latest()
        ->limit(5)
        ->get();
        
        foreach ($recentBookings as $booking) {
            $customerName = $booking->user?->name ?: $booking->customer_name;
            $activities->push([
                'type' => 'booking',
                'icon' => 'shopping-cart',
                'color' => 'success',
                'title' => "New booking from {$customerName}",
                'description' => "{$booking->quantity} ticket(s) for {$booking->event->title}",
                'amount' => $booking->total_amount,
                'time' => $booking->created_at,
                'time_human' => $booking->created_at->diffForHumans(),
            ]);
            
            // Add payment activity if paid
            if ($booking->payment_status === 'paid') {
                $activities->push([
                    'type' => 'payment',
                    'icon' => 'credit-card',
                    'color' => 'success',
                    'title' => "Payment received",
                    'description' => "From {$customerName} for {$booking->event->title}",
                    'amount' => $booking->total_amount,
                    'time' => $booking->updated_at,
                    'time_human' => $booking->updated_at->diffForHumans(),
                ]);
            }
        }
        
        // Get recently published events
        $recentEvents = Event::where('organizer_id', $organizerId)
            ->where('status', 'published')
            ->where('updated_at', '>=', Carbon::now()->subDays(7))
            ->latest('updated_at')
            ->limit(3)
            ->get();
        
        foreach ($recentEvents as $event) {
            $activities->push([
                'type' => 'listing',
                'icon' => 'sparkles',
                'color' => 'info',
                'title' => 'Listing went live',
                'description' => $event->title,
                'amount' => null,
                'time' => $event->updated_at,
                'time_human' => $event->updated_at->diffForHumans(),
            ]);
        }
        
        // Get recent ticket scans (simulated - you'd connect to actual scanning system)
        $recentScans = Ticket::whereHas('event', function ($query) use ($organizerId) {
            $query->where('organizer_id', $organizerId);
        })
        ->where('status', 'used')
        ->where('updated_at', '>=', Carbon::now()->subDays(1))
        ->with('event')
        ->latest('updated_at')
        ->limit(3)
        ->get();
        
        foreach ($recentScans as $ticket) {
            $activities->push([
                'type' => 'scan',
                'icon' => 'qrcode',
                'color' => 'primary',
                'title' => 'Ticket scanned',
                'description' => "At {$ticket->event->title}",
                'amount' => null,
                'time' => $ticket->updated_at,
                'time_human' => $ticket->updated_at->diffForHumans(),
            ]);
        }
        
        // Sort all activities by time (newest first) and limit
        return $activities->sortByDesc('time')->take(8)->values();
    }
}