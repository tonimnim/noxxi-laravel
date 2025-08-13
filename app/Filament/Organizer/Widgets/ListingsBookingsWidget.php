<?php

namespace App\Filament\Organizer\Widgets;

use App\Models\Event;
use App\Models\Booking;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class ListingsBookingsWidget extends Widget
{
    protected static string $view = 'filament.organizer.widgets.listings-bookings-widget';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    
    #[Url]
    public string $activeTab = 'listings';
    
    public string $search = '';
    public string $vertical = 'all';
    public string $status = 'all';
    public string $listingStatus = 'all';
    
    public function switchToListings()
    {
        $this->activeTab = 'listings';
    }
    
    public function switchToBookings()
    {
        $this->activeTab = 'bookings';
    }
    
    public function getListingsProperty()
    {
        $organizerId = Auth::user()->organizer?->id;
        
        if (!$organizerId) {
            return collect([]);
        }
        
        $query = Event::where('organizer_id', $organizerId)
            ->with(['category', 'bookings']);
        
        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('listing_code', 'like', "%{$this->search}%")
                  ->orWhere('location', 'like', "%{$this->search}%");
            });
        }
        
        // Apply vertical filter
        if ($this->vertical !== 'all') {
            $query->whereHas('category', function ($q) {
                $q->where('name', $this->vertical);
            });
        }
        
        // Apply status filter
        if ($this->listingStatus !== 'all') {
            $query->where('status', $this->listingStatus);
        }
        
        return $query->latest()->limit(10)->get();
    }
    
    public function getBookingsProperty()
    {
        $organizerId = Auth::user()->organizer?->id;
        
        if (!$organizerId) {
            return collect([]);
        }
        
        $query = Booking::whereHas('event', function ($q) use ($organizerId) {
            $q->where('organizer_id', $organizerId);
        })->with(['event', 'event.category', 'user']);
        
        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('booking_reference', 'like', "%{$this->search}%")
                  ->orWhereHas('user', function ($q) {
                      $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                  })
                  ->orWhere('customer_name', 'like', "%{$this->search}%")
                  ->orWhere('customer_email', 'like', "%{$this->search}%")
                  ->orWhereHas('event', function ($q) {
                      $q->where('title', 'like', "%{$this->search}%");
                  });
            });
        }
        
        // Apply vertical filter
        if ($this->vertical !== 'all') {
            $query->whereHas('event.category', function ($q) {
                $q->where('name', $this->vertical);
            });
        }
        
        // Apply status filter
        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }
        
        return $query->latest()->limit(10)->get();
    }
    
    public function getVerticalsProperty()
    {
        return [
            'all' => 'All verticals',
            'Events' => 'Events',
            'Sports' => 'Sports',
            'Wellness & Spa' => 'Wellness & Spa',
            'Travel' => 'Travel',
            'Music & Arts' => 'Music & Arts',
        ];
    }
    
    public function getStatusesProperty()
    {
        return [
            'all' => 'All statuses',
            'confirmed' => 'Confirmed',
            'pending' => 'Pending',
            'cancelled' => 'Cancelled',
        ];
    }
    
    public function getListingStatusesProperty()
    {
        return [
            'all' => 'All statuses',
            'live' => 'Live',
            'published' => 'Published',
            'paused' => 'Paused',
            'sold_out' => 'Sold Out',
            'draft' => 'Draft',
        ];
    }
}