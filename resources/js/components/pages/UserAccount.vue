<template>
  <div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8" style="margin-top: 100px;">
      <!-- Page Title -->
      <h1 class="text-2xl font-bold text-gray-900 mb-8">My Tickets</h1>
      
      <!-- Tabs -->
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === tab.id
                ? 'border-[#FDB813] text-gray-900'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            {{ tab.name }}
            <span v-if="tab.count > 0" class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-100">
              {{ tab.count }}
            </span>
          </button>
        </nav>
      </div>
      
      <!-- Tab Content -->
      <div class="mt-8">
        <!-- Loading State -->
        <div v-if="isLoading && activeTab === 'upcoming'" class="text-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#FDB813] mx-auto"></div>
          <p class="mt-4 text-sm text-gray-500">Loading your tickets...</p>
        </div>
        
        <!-- Error State -->
        <div v-else-if="hasError && activeTab === 'upcoming'" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">Error loading tickets</h3>
          <p class="mt-1 text-sm text-gray-500">Please refresh the page or try again later.</p>
          <button @click="loadTickets" class="mt-4 px-4 py-2 bg-[#FDB813] text-white rounded hover:bg-[#FDB813]/90">
            Retry
          </button>
        </div>
        
        <!-- Upcoming Tickets -->
        <div v-else-if="activeTab === 'upcoming' && !isLoading">
          <div v-if="upcomingTickets.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No upcoming events</h3>
            <p class="mt-1 text-sm text-gray-500">Browse events to book your tickets.</p>
            <div class="mt-6">
              <a href="/" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-[#FDB813] hover:bg-[#FDB813]/90">
                Browse Events
              </a>
            </div>
          </div>
          
          <!-- Ticket Cards -->
          <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div v-for="ticket in upcomingTickets" :key="ticket.id" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
              <div class="flex justify-between items-start mb-3">
                <div>
                  <h3 class="font-semibold text-gray-900">{{ ticket.event?.title || 'Event' }}</h3>
                  <p class="text-sm text-gray-500 mt-1">{{ ticket.event?.venue_name || '' }}</p>
                </div>
                <span :class="[
                  'px-2 py-1 text-xs font-medium rounded',
                  ticket.status === 'valid' ? 'bg-green-100 text-green-800' : 
                  ticket.status === 'transferred' ? 'bg-blue-100 text-blue-800' :
                  'bg-gray-100 text-gray-800'
                ]">
                  {{ ticket.status === 'valid' ? 'Valid' : 
                     ticket.status === 'transferred' ? 'Transferred' : 
                     ticket.status }}
                </span>
              </div>
              <div class="text-sm text-gray-600 space-y-1">
                <p><strong>Date:</strong> {{ formatDate(ticket.event?.event_date) }}</p>
                <p><strong>Type:</strong> {{ ticket.ticket_type }}</p>
                <p><strong>Code:</strong> {{ ticket.ticket_code }}</p>
                <p v-if="ticket.seat_number"><strong>Seat:</strong> {{ ticket.seat_section ? ticket.seat_section + ' - ' : '' }}{{ ticket.seat_number }}</p>
                <p><strong>Price:</strong> {{ ticket.currency }} {{ ticket.price }}</p>
              </div>
              <button 
                @click="viewTicket(ticket)"
                class="mt-4 w-full px-4 py-2 text-sm font-medium text-[#FDB813] border border-[#FDB813] rounded hover:bg-[#FDB813] hover:text-white transition-colors">
                View Ticket
              </button>
            </div>
          </div>
        </div>
        
        <!-- Past Events -->
        <div v-if="activeTab === 'past'">
          <div v-if="pastTickets.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No past events</h3>
            <p class="mt-1 text-sm text-gray-500">Your attended events will appear here.</p>
          </div>
          
          <!-- Past Ticket Cards -->
          <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div v-for="ticket in pastTickets" :key="ticket.id" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 opacity-75">
              <div class="flex justify-between items-start mb-3">
                <div>
                  <h3 class="font-semibold text-gray-900">{{ ticket.event?.title || 'Event' }}</h3>
                  <p class="text-sm text-gray-500 mt-1">{{ ticket.event?.venue_name || '' }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded">
                  Past
                </span>
              </div>
              <div class="text-sm text-gray-600 space-y-1">
                <p><strong>Date:</strong> {{ formatDate(ticket.event?.event_date) }}</p>
                <p><strong>Type:</strong> {{ ticket.ticket_type }}</p>
                <p><strong>Code:</strong> {{ ticket.ticket_code }}</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Cancelled -->
        <div v-if="activeTab === 'cancelled'" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No cancelled tickets</h3>
          <p class="mt-1 text-sm text-gray-500">Cancelled tickets will appear here.</p>
        </div>
        
        <!-- Request Refund -->
        <div v-if="activeTab === 'refund'">
          <div v-if="eligibleRefundTickets.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No tickets eligible for refund</h3>
            <p class="mt-1 text-sm text-gray-500">You can request refunds for valid tickets to upcoming events.</p>
          </div>
          
          <!-- Refundable Ticket Cards -->
          <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div v-for="ticket in eligibleRefundTickets" :key="ticket.id" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
              <div class="flex justify-between items-start mb-3">
                <div>
                  <h3 class="font-semibold text-gray-900">{{ ticket.event?.title || 'Event' }}</h3>
                  <p class="text-sm text-gray-500 mt-1">{{ ticket.event?.venue_name || '' }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded">
                  Refundable
                </span>
              </div>
              <div class="text-sm text-gray-600 space-y-1">
                <p><strong>Date:</strong> {{ formatDate(ticket.event?.event_date) }}</p>
                <p><strong>Type:</strong> {{ ticket.ticket_type }}</p>
                <p><strong>Code:</strong> {{ ticket.ticket_code }}</p>
                <p><strong>Price:</strong> {{ ticket.currency }} {{ ticket.price }}</p>
              </div>
              <button 
                @click="requestRefund(ticket)"
                class="mt-4 w-full px-4 py-2 text-sm font-medium text-white bg-red-600 rounded hover:bg-red-700 transition-colors">
                Request Refund
              </button>
            </div>
          </div>
        </div>
        
        <!-- Scan Tickets (Only visible to users with scanner permissions) -->
        <div v-if="activeTab === 'scan' && canScanTickets">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            
            <!-- Launch Scanner Button -->
            <div class="flex flex-col items-center justify-center py-8">
              <svg class="w-16 h-16 text-[#FDB813] mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
              </svg>
              
              <a 
                href="/scanner/check-in"
                class="inline-flex items-center px-6 py-3 text-base font-medium text-white bg-[#FDB813] rounded-lg hover:bg-[#FDB813]/90 transition-colors"
              >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
                Scan Tickets
              </a>
            </div>
            
          </div>
        </div>
      </div>
    </div>
    
    <!-- Ticket Modal -->
    <TicketModal 
      v-if="selectedTicket"
      :ticket="selectedTicket" 
      :is-open="showTicketModal" 
      @close="closeTicketModal" 
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import TicketModal from '../checkout/TicketModal.vue'

// State
const activeTab = ref('upcoming')
const upcomingTickets = ref([])
const pastTickets = ref([])
const cancelledTickets = ref([])
const eligibleRefundTickets = ref([])
const selectedTicket = ref(null)
const showTicketModal = ref(false)
const isLoading = ref(true)
const hasError = ref(false)
const canScanTickets = ref(false)
const scannerData = ref(null)

// Tabs configuration
const tabs = ref([
  { id: 'upcoming', name: 'Upcoming', count: 0 },
  { id: 'past', name: 'Past Events', count: 0 },
  { id: 'cancelled', name: 'Cancelled', count: 0 },
  { id: 'refund', name: 'Request Refund', count: 0 }
])

// Load tickets on mount
onMounted(async () => {
  // Use initial data if available (from server-side rendering)
  if (window.__INITIAL_DATA__) {
    // Load tickets
    if (window.__INITIAL_DATA__.tickets) {
      upcomingTickets.value = window.__INITIAL_DATA__.tickets
      tabs.value[0].count = upcomingTickets.value.length
    }
    
    // Check for scanner permissions
    if (window.__INITIAL_DATA__.canScanTickets) {
      canScanTickets.value = true
      scannerData.value = window.__INITIAL_DATA__.scannerData
      
      // Add scan tickets tab after request refund
      tabs.value.push({
        id: 'scan',
        name: 'Scan Tickets',
        count: scannerData.value?.permissions?.totalEvents || 0,
        icon: 'qrcode' // Optional icon indicator
      })
      
      // Auto-download manifests for upcoming events (next 48 hours)
      await autoDownloadManifests()
    }
    
    isLoading.value = false
  } else {
    // Fallback to API call if no initial data
    await loadTickets()
  }
})

// Methods
const loadTickets = async () => {
  isLoading.value = true
  hasError.value = false
  
  try {
    const response = await fetch('/user/tickets/upcoming', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      credentials: 'same-origin'
    })
    
    if (response.ok) {
      const data = await response.json()
      upcomingTickets.value = data.data?.tickets || []
      tabs.value[0].count = upcomingTickets.value.length
    } else if (response.status === 401) {
      // Session expired, redirect to login
      window.location.href = '/login'
    } else {
      hasError.value = true
    }
  } catch (error) {
    hasError.value = true
  } finally {
    isLoading.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'Date TBD'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Auto-download manifests for upcoming events
const autoDownloadManifests = async () => {
  if (!scannerData.value?.allowedEvents?.length) return
  
  try {
    // Import scanner utilities dynamically (only when needed)
    const { default: scannerDB } = await import('../../utils/scanner-db')
    const { default: scannerSync } = await import('../../services/scanner-sync')
    
    // Initialize scanner DB
    await scannerDB.init()
    
    // Filter events happening in next 48 hours
    const upcomingEvents = scannerData.value.allowedEvents.filter(event => {
      if (!event.date) return false // Services always download
      const eventDate = new Date(event.date)
      const hoursUntilEvent = (eventDate - new Date()) / (1000 * 60 * 60)
      return hoursUntilEvent <= 48 && hoursUntilEvent >= -24 // 48hrs before to 24hrs after
    })
    
    if (upcomingEvents.length === 0) {
      console.log('No upcoming events to cache')
      return
    }
    
    console.log(`Auto-downloading manifests for ${upcomingEvents.length} upcoming events`)
    
    // Prepare permissions for caching
    const permissions = {
      user_id: window.__INITIAL_DATA__.user.id,
      role: scannerData.value.permissions.isOrganizer ? 'organizer' : 'manager',
      organizer_id: scannerData.value.organizerIds[0],
      allowed_events: scannerData.value.allowedEvents.map(e => e.id)
    }
    
    // Download manifests in background (don't block UI)
    for (const event of upcomingEvents) {
      try {
        // Check if already cached and recent
        const existing = await scannerDB.getEventManifest(event.id)
        if (existing && existing.cache_age && existing.cache_age.includes('minutes')) {
          console.log(`Event ${event.title} already cached recently`)
          continue
        }
        
        // Download manifest
        const result = await scannerSync.downloadEventManifest(event.id, permissions)
        if (result.success) {
          console.log(`Cached ${result.ticket_count} tickets for ${event.title}`)
        }
      } catch (error) {
        console.error(`Failed to cache event ${event.title}:`, error)
        // Continue with other events even if one fails
      }
    }
    
    console.log('Manifest pre-loading complete')
  } catch (error) {
    console.error('Failed to auto-download manifests:', error)
    // Non-critical error - scanner will still work with manual download
  }
}

const loadPastTickets = async () => {
  try {
    const response = await fetch('/user/tickets/past', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      credentials: 'same-origin'
    })
    
    if (response.ok) {
      const data = await response.json()
      pastTickets.value = data.data?.tickets || []
      tabs.value[1].count = pastTickets.value.length
    }
  } catch (error) {
    // Silently handle error for past tickets
  }
}

const viewTicket = (ticket) => {
  selectedTicket.value = ticket
  showTicketModal.value = true
}

const closeTicketModal = () => {
  showTicketModal.value = false
  // Keep selectedTicket for a moment to avoid flicker during close animation
  setTimeout(() => {
    selectedTicket.value = null
  }, 300)
}

// Watch tab changes to load appropriate data
watch(activeTab, (newTab) => {
  if (newTab === 'past' && pastTickets.value.length === 0) {
    loadPastTickets()
  } else if (newTab === 'refund') {
    loadRefundableTickets()
  }
})

const loadRefundableTickets = async () => {
  // Show all valid upcoming tickets - organizer will decide based on their policy
  eligibleRefundTickets.value = upcomingTickets.value.filter(ticket => {
    if (!ticket.event?.event_date) return false
    const eventDate = new Date(ticket.event.event_date)
    const now = new Date()
    // Only show tickets for future events with valid status
    return eventDate > now && ticket.status === 'valid'
  })
  
  tabs.value[3].count = eligibleRefundTickets.value.length
}

const requestRefund = async (ticket) => {
  if (confirm(`Request refund for ${ticket.event?.title}?`)) {
    try {
      // First, get the booking ID from the ticket
      const bookingId = ticket.booking_id
      
      if (!bookingId) {
        alert('Unable to process refund request. Booking information not found.')
        return
      }
      
      // Create a refund request using web route (session auth)
      const response = await fetch('/refund-requests', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          booking_id: bookingId,
          ticket_id: ticket.id,
          requested_amount: ticket.price,
          currency: ticket.currency,
          reason: 'Customer requested refund via account page',
          customer_message: 'Refund requested by customer'
        })
      })
      
      if (response.ok) {
        const data = await response.json()
        alert('Refund request sent successfully')
        
        // Remove ticket from eligible list
        eligibleRefundTickets.value = eligibleRefundTickets.value.filter(t => t.id !== ticket.id)
        // Update count
        tabs.value[3].count = eligibleRefundTickets.value.length
        // Reload tickets to refresh the list
        await loadTickets()
      } else if (response.status === 404) {
        alert('Refund system unavailable. Please contact support.')
      } else {
        const data = await response.json()
        alert(data.message || 'Failed to submit refund request. Please contact support.')
      }
    } catch (error) {
      alert('Failed to submit refund request. Please contact support.')
    }
  }
}
</script>