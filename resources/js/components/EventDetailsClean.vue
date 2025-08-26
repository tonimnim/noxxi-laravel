<template>
  <div class="h-[calc(100vh-64px)] overflow-y-auto" @scroll="handleContentScroll" ref="contentContainer">
    <!-- Notification Toast -->
    <transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="transform -translate-y-full opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="transform translate-y-0 opacity-100"
      leave-to-class="transform -translate-y-full opacity-0"
    >
      <div v-if="notification.show" class="fixed top-20 left-1/2 transform -translate-x-1/2 z-50">
        <div class="bg-[#FDB813] text-black px-6 py-4 rounded-lg shadow-lg flex items-center gap-3 min-w-[350px]">
          <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <p class="font-medium">{{ notification.message }}</p>
        </div>
      </div>
    </transition>

    <!-- Split Background Layout -->
    <div class="relative">
      <!-- Upper Half Background -->
      <div class="absolute inset-0 h-[calc(50vh+30px)] bg-[#305F64]"></div>
      
      <!-- Content Container -->
      <div class="relative">
        <!-- Header Section -->
        <div class="container mx-auto px-4 pt-32 pb-8">
          <div class="grid lg:grid-cols-12 gap-8">
            <!-- Left Content -->
            <div class="lg:col-span-8 ml-8">
              <!-- Event Title -->
              <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-white mb-3 max-w-2xl">{{ event.title }}</h1>
              
              <!-- Event Meta -->
              <div class="flex items-center gap-6 text-gray-200 mb-6">
                <span class="text-[#FDB813] font-semibold text-lg">{{ formatEventDate }}</span>
                <span class="text-gray-300">{{ event.venue?.name }}</span>
              </div>
              
              <!-- Read More Button -->
              <button 
                @click="showDescription = !showDescription"
                class="text-gray-300 hover:text-white transition-colors text-sm font-medium"
              >
                {{ showDescription ? 'Show Less' : 'Read More' }}
              </button>
              
              <!-- Description (Expandable) - Sits on bg color without white container -->
              <div v-if="showDescription" class="mt-6 max-w-2xl">
                <div class="text-gray-200 leading-relaxed" v-html="event.description"></div>
              </div>

              <!-- Tickets Section -->
              <div class="mt-12">
                <h2 class="text-xl font-bold text-gray-900 mb-4">TICKETS</h2>
                
                <!-- Tickets Card -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden max-w-2xl">
                  <!-- Ticket Items -->
                  <div class="p-4">
                    <div 
                      v-for="(ticket, index) in ticketTypes" 
                      :key="index"
                      class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0"
                    >
                      <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900">{{ ticket.name }}</h3>
                        <p class="text-gray-700 text-sm font-medium">{{ event.pricing?.currency || 'KES' }} {{ formatPrice(ticket.price) }}</p>
                        <p v-if="ticket.description" class="text-xs text-gray-500">{{ ticket.description }}</p>
                        <p v-if="ticket.status === 'Sold Out'" class="text-xs text-red-500 font-medium">Sold Out</p>
                      </div>
                      
                      <!-- Quantity Selector -->
                      <div class="flex items-center gap-2">
                        <button 
                          @click="decrementTicket(index)"
                          :disabled="ticketQuantities[index] === 0 || ticket.status === 'Sold Out'"
                          class="w-8 h-8 bg-[#305F64] hover:bg-[#305F64]/90 disabled:bg-gray-200 disabled:cursor-not-allowed text-white rounded flex items-center justify-center transition-colors"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                          </svg>
                        </button>
                        
                        <span class="w-10 text-center text-base font-semibold text-gray-900">{{ ticketQuantities[index] }}</span>
                        
                        <button 
                          @click="incrementTicket(index)"
                          :disabled="ticketQuantities[index] >= (ticket.quantity || 10) || ticket.status === 'Sold Out'"
                          class="w-8 h-8 bg-[#305F64] hover:bg-[#305F64]/90 disabled:bg-gray-200 disabled:cursor-not-allowed text-white rounded flex items-center justify-center transition-colors"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Total Section with Gray Background -->
                  <div class="bg-gray-50 p-4">
                    <div class="flex justify-between items-center mb-3">
                      <span class="text-base font-semibold text-gray-900">Total</span>
                      <span class="text-lg font-bold text-gray-900">{{ event.pricing?.currency || 'KES' }} {{ formatPrice(totalAmount) }}</span>
                    </div>
                
                    <!-- Details Breakdown -->
                    <div class="space-y-2 mb-4">
                      <!-- Tickets Summary -->
                      <div>
                        <p class="text-xs font-medium text-gray-700 mb-1">Tickets</p>
                        <div v-if="totalTickets > 0" class="text-xs text-gray-600 space-y-0.5">
                          <div v-for="(ticket, idx) in ticketTypes" :key="idx" v-show="ticketQuantities[idx] > 0" class="flex justify-between">
                            <span>{{ ticketQuantities[idx] }} x {{ ticket.name }}</span>
                            <span>{{ event.pricing?.currency || 'KES' }} {{ formatPrice(ticket.price * ticketQuantities[idx]) }}</span>
                          </div>
                        </div>
                        <div v-else class="text-xs text-gray-500">No tickets selected</div>
                      </div>
                      
                      <!-- Discounts & Fees -->
                      <div>
                        <p class="text-xs font-medium text-gray-700 mb-1">Discounts & Fees</p>
                        <div class="text-xs text-gray-600 space-y-0.5">
                          <div class="flex justify-between">
                            <span>Processing fees</span>
                            <span>{{ event.pricing?.currency || 'KES' }} 0</span>
                          </div>
                          <div class="flex justify-between">
                            <span>Applied discount (0%)</span>
                            <span>{{ event.pricing?.currency || 'KES' }} 0</span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Purchase Button -->
                    <button 
                      @click="handlePurchase"
                      :disabled="totalTickets === 0"
                      class="w-full py-3 bg-[#FDB813] hover:bg-[#FDB813]/90 disabled:bg-gray-300 disabled:cursor-not-allowed text-gray-900 font-semibold rounded transition-colors text-sm"
                    >
                      Purchase tickets
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Right Column - Event Poster -->
            <div class="lg:col-span-4">
              <div class="sticky top-32">
                <img 
                  v-if="event.media?.cover_image"
                  :src="event.media.cover_image" 
                  :alt="event.title"
                  class="w-full rounded-xl shadow-2xl"
                />
                <div v-else class="w-full aspect-[3/4] bg-gradient-to-br from-[#305F64] to-[#1a2332] rounded-xl flex items-center justify-center">
                  <span class="text-white text-xl">Event Image</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Similar Events Section -->
        <div v-if="similarEvents && similarEvents.length > 0" class="container mx-auto px-4 mt-20 pb-16">
          <h2 class="text-2xl font-bold text-gray-900 mb-2">SIMILAR EVENTS</h2>
          <p class="text-gray-600 mb-8">Events near you</p>
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div 
              v-for="similarEvent in similarEvents.slice(0, 4)" 
              :key="similarEvent.id"
              class="bg-white rounded-lg overflow-hidden cursor-pointer hover:shadow-lg transition-shadow group"
              @click="goToEvent(similarEvent)"
            >
              <div class="h-48 bg-gray-200 overflow-hidden">
                <img 
                  v-if="similarEvent.cover_image_url"
                  :src="similarEvent.cover_image_url" 
                  :alt="similarEvent.title"
                  class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                />
              </div>
              <div class="p-4">
                <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">{{ similarEvent.title }}</h3>
                <p class="text-sm text-[#305F64] font-medium mb-1">{{ formatDate(similarEvent.event_date) }}</p>
                <p class="text-sm text-gray-600">{{ similarEvent.venue_name }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Checkout Modal -->
    <checkout-modal
      v-if="showCheckout"
      :event="event"
      :selected-tickets="selectedTicketsForCheckout"
      :total-amount="totalAmount"
      @close="showCheckout = false"
      @complete="handleCheckoutComplete"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import CheckoutModal from './CheckoutModal.vue'

// Props
const props = defineProps({
  event: {
    type: Object,
    required: true
  }
})

// State
const showDescription = ref(false)
const showCheckout = ref(false)
const ticketQuantities = ref([])
const similarEvents = ref([])
const contentContainer = ref(null)
const notification = ref({ show: false, message: '' })
let lastScrollY = 0

// Initialize ticket quantities
const ticketTypes = computed(() => {
  return props.event.pricing?.ticket_types || []
})

// Initialize quantities to 0
onMounted(() => {
  ticketQuantities.value = new Array(ticketTypes.value.length).fill(0)
  loadSimilarEvents()
  
  // Check if we have pending checkout after registration
  const pendingCheckout = sessionStorage.getItem('pendingCheckout')
  if (pendingCheckout) {
    try {
      const checkout = JSON.parse(pendingCheckout)
      if (checkout.eventId === props.event.id) {
        // Restore ticket selection
        checkout.tickets.forEach((ticket, index) => {
          const ticketIndex = ticketTypes.value.findIndex(t => t.name === ticket.name)
          if (ticketIndex !== -1) {
            ticketQuantities.value[ticketIndex] = ticket.quantity
          }
        })
        
        // Clear pending checkout
        sessionStorage.removeItem('pendingCheckout')
        
        // Auto-open checkout modal after a brief delay
        setTimeout(() => {
          if (totalTickets.value > 0) {
            showCheckout.value = true
          }
        }, 500)
      }
    } catch (error) {
      // Invalid data, clear it
      sessionStorage.removeItem('pendingCheckout')
    }
  }
})

// Computed
const totalTickets = computed(() => {
  return ticketQuantities.value.reduce((sum, qty) => sum + qty, 0)
})

const totalAmount = computed(() => {
  return ticketTypes.value.reduce((sum, ticket, index) => {
    return sum + (ticket.price * ticketQuantities.value[index])
  }, 0)
})

const selectedTicketsForCheckout = computed(() => {
  return ticketTypes.value
    .map((ticket, index) => ({
      ...ticket,
      quantity: ticketQuantities.value[index]
    }))
    .filter(ticket => ticket.quantity > 0)
})

const formatEventDate = computed(() => {
  if (!props.event.dates?.event_date) return ''
  const date = new Date(props.event.dates.event_date)
  return date.toLocaleDateString('en-US', { 
    day: '2-digit',
    month: 'short'
  })
})

// Methods
const formatPrice = (price) => {
  return new Intl.NumberFormat().format(price || 0)
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const incrementTicket = (index) => {
  const maxQty = ticketTypes.value[index].quantity || 10
  if (ticketQuantities.value[index] < maxQty) {
    ticketQuantities.value[index]++
  }
}

const decrementTicket = (index) => {
  if (ticketQuantities.value[index] > 0) {
    ticketQuantities.value[index]--
  }
}

const handlePurchase = async () => {
  if (totalTickets.value > 0) {
    // Check if user is authenticated using web session
    try {
      const response = await fetch('/auth/web/check', {
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'include'
      })
      
      const data = await response.json()
      
      if (response.ok && data.authenticated === true) {
        // User is authenticated, proceed with checkout directly - NO NOTIFICATION
        showCheckout.value = true
      } else {
        // User not authenticated, show notification then redirect
        showNotification('Please sign in to continue with your purchase')
        
        // Save checkout data
        sessionStorage.setItem('pendingCheckout', JSON.stringify({
          eventId: props.event.id,
          eventSlug: props.event.slug,
          tickets: selectedTicketsForCheckout.value,
          totalAmount: totalAmount.value
        }))
        
        // Redirect to login after notification is shown
        setTimeout(() => {
          window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`
        }, 3000)
      }
    } catch (error) {
      console.error('Auth check error:', error)
      // Not authenticated, show notification then redirect
      showNotification('Please sign in to continue with your purchase')
      
      // Save checkout data
      sessionStorage.setItem('pendingCheckout', JSON.stringify({
        eventId: props.event.id,
        eventSlug: props.event.slug,
        tickets: selectedTicketsForCheckout.value,
        totalAmount: totalAmount.value
      }))
      
      // Redirect to login after a short delay
      setTimeout(() => {
        window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`
      }, 3000)
    }
  }
}

const handleCheckoutComplete = (bookingId) => {
  // Redirect to booking confirmation
  window.location.href = `/booking/confirmation/${bookingId}`
}

const goToEvent = (event) => {
  window.location.href = `/listings/${event.slug || event.id}`
}

const showNotification = (message) => {
  notification.value = { show: true, message }
  // Auto-hide after 6 seconds
  setTimeout(() => {
    notification.value.show = false
  }, 6000)
}

const loadSimilarEvents = async () => {
  // Skip if no category
  if (!props.event.category?.id) {
    return
  }
  
  try {
    // Use simpler API call without the exclude filter which causes issues
    const response = await fetch(`/api/events?limit=4`, {
      headers: {
        'Accept': 'application/json'
      }
    })
    
    if (!response.ok) {
      // Silently fail for similar events - it's optional content
      return
    }
    
    const data = await response.json()
    if (data.success && data.data?.events) {
      // Filter out current event on client side
      similarEvents.value = data.data.events.filter(e => e.id !== props.event.id).slice(0, 4)
    }
  } catch (error) {
    // Silently fail - similar events are optional
  }
}

const handleContentScroll = () => {
  const currentScrollY = contentContainer.value?.scrollTop || 0
  
  // Emit custom event for header to listen to
  const event = new CustomEvent('content-scroll', {
    detail: {
      scrollY: currentScrollY,
      direction: currentScrollY > lastScrollY ? 'down' : 'up'
    }
  })
  window.dispatchEvent(event)
  
  lastScrollY = currentScrollY
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>