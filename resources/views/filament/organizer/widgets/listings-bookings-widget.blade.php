<x-filament-widgets::widget class="fi-wi-simple">
    <div class="space-y-4">
        {{-- Search and Filters Container --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center">
                {{-- Search Bar --}}
                <div class="flex-1">
                    <div class="relative">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="search" 
                            placeholder="Search listings or bookings..."
                            class="w-full pl-10 pr-3 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                        >
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                {{-- Filters --}}
                <div class="flex gap-2">
                    <select 
                        wire:model.live="vertical" 
                        class="px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 cursor-pointer transition-colors hover:bg-gray-50"
                    >
                        @foreach($this->verticals as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    
                    <select 
                        wire:model.live="status" 
                        class="px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 cursor-pointer transition-colors hover:bg-gray-50"
                    >
                        @foreach($this->statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    
                    @if($activeTab === 'listings')
                        <select 
                            wire:model.live="listingStatus" 
                            class="px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 cursor-pointer transition-colors hover:bg-gray-50"
                        >
                            @foreach($this->listingStatuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    @else
                        <select 
                            class="px-3 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 cursor-pointer transition-colors hover:bg-gray-50"
                        >
                            <option>All statuses</option>
                        </select>
                    @endif
                </div>
            </div>
        </div>

        {{-- Toggle Buttons and Table Container --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            {{-- Toggle Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <button 
                        wire:click="switchToListings"
                        class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-md transition-all {{ $activeTab === 'listings' ? 'text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Listings
                    </button>
                    <button 
                        wire:click="switchToBookings"
                        class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-md transition-all {{ $activeTab === 'bookings' ? 'text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Bookings
                    </button>
                </div>
                
            </div>
            
            {{-- Table Title --}}
            <div class="px-6 py-3 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ $activeTab === 'listings' ? 'Listings' : 'Bookings' }}
                </h3>
            </div>

            {{-- Table Content --}}
            <div class="overflow-x-auto">
                @if($activeTab === 'listings')
                    {{-- Listings Table --}}
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <th class="text-left py-3 px-6 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Listing</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Vertical</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Status</th>
                                <th class="text-right py-3 px-4 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">From</th>
                                <th class="text-center py-3 px-4 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Sold</th>
                                <th class="text-right py-3 px-4 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Revenue</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Date(s)</th>
                                <th class="text-center py-3 px-4 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($this->listings as $listing)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <td class="py-3 px-6">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $listing->title }}</div>
                                            <div class="text-xs text-gray-500">{{ $listing->listing_code }} • {{ $listing->location ?: $listing->city }}</div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        @php
                                            $categoryName = $listing->category->name ?? 'Events';
                                            $colors = [
                                                'Events' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                                'Sports' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                                                'Wellness & Spa' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                'Travel' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                                'Music & Arts' => 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300',
                                            ];
                                            $colorClass = $colors[$categoryName] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
                                            {{ $categoryName }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        @php
                                            $status = $listing->status;
                                            $statusColors = [
                                                'live' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                'published' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                'paused' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                                'sold_out' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
                                            ];
                                            $statusColor = $statusColors[strtolower($status)] ?? 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                            {{ ucfirst($status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">${{ number_format($listing->price, 2) }}</td>
                                    <td class="py-3 px-4 text-center">
                                        {{ $listing->bookings()->where('status', 'confirmed')->sum('quantity') }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        ${{ number_format($listing->bookings()->where('status', 'confirmed')->where('payment_status', 'paid')->sum('total_amount'), 2) }}
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($listing->end_date && $listing->end_date != $listing->event_date)
                                            {{ $listing->event_date->format('M d') }} - {{ $listing->end_date->format('M d, Y') }}
                                        @else
                                            {{ $listing->event_date->format('M d, Y') }}
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-gray-500">No listings found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    {{-- Bookings Table --}}
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <th class="text-left py-3 px-6 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Booking</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Customer</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Listing</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Vertical</th>
                                <th class="text-center py-3 px-4 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Qty</th>
                                <th class="text-right py-3 px-4 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Total</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Status</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Date</th>
                                <th class="text-center py-3 px-4 font-medium text-gray-600 dark:text-gray-400 text-xs uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($this->bookings as $booking)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                    <td class="py-3 px-6 font-medium">{{ $booking->booking_reference }}</td>
                                    <td class="py-3 px-4">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ $booking->user?->name ?: $booking->customer_name }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $booking->user?->email ?: $booking->customer_email }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">{{ Str::limit($booking->event?->title, 30) }}</td>
                                    <td class="py-3 px-4">
                                        @php
                                            $categoryName = $booking->event?->category?->name ?? 'Events';
                                            $colors = [
                                                'Events' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                                'Sports' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                                                'Wellness & Spa' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                'Travel' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                                'Music & Arts' => 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300',
                                            ];
                                            $colorClass = $colors[$categoryName] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
                                            {{ $categoryName }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">{{ $booking->quantity }}</td>
                                    <td class="py-3 px-4 text-right">${{ number_format($booking->total_amount, 2) }}</td>
                                    <td class="py-3 px-4">
                                        @php
                                            $status = $booking->status;
                                            $statusColors = [
                                                'confirmed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                            ];
                                            $statusColor = $statusColors[strtolower($status)] ?? 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                            {{ ucfirst($status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">{{ $booking->created_at->format('Y-m-d') }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="py-8 text-center text-gray-500">No bookings found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-filament-widgets::widget>