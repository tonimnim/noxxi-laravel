<template>
  <div class="min-h-screen bg-[#1a2332]">
    <!-- Main Content Container -->
    <div class="container mx-auto px-4 py-8">
      <div class="grid lg:grid-cols-3 gap-8">
        <!-- Left Column - Event Info and Tickets -->
        <div class="lg:col-span-2">
          <!-- Event Title Section -->
          <div class="mb-8">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">{{ event.title }}</h1>
            <div class="flex items-center gap-4 text-lg text-gray-300">
              <span class="text-[#FDB813]">{{ formatEventDate }}</span>
              <span>•</span>
              <span>{{ event.venue?.name }}</span>
            </div>
            
            <!-- Read More Button -->
            <button 
              @click="showDescription = !showDescription"
              class="mt-4 text-gray-400 hover:text-white transition-colors text-sm"
            >
              Read More
            </button>
          </div>

          <!-- Description Section (Hidden by default) -->
          <div v-if="showDescription" class="mb-8 p-6 bg-[#253345] rounded-lg">
            <h2 class="text-xl font-bold text-white mb-4">About This Event</h2>
            <div class="text-gray-300" v-html="event.description"></div>
          </div>

          <!-- Tickets Section -->
          <div>
            <h2 class="text-2xl font-bold text-white mb-6">TICKETS</h2>
            
            <div class="bg-[#253345] rounded-xl p-6">
              <div class="space-y-4">
                <div 
                  v-for="(ticket, index) in ticketTypes" 
                  :key="index"
                  class="flex items-center justify-between py-4 border-b border-gray-700 last:border-0"
                >
                  <div class="flex-1">
                    <h3 class="text-lg font-semibold text-white">{{ ticket.name }}</h3>
                    <p class="text-gray-400">{{ event.pricing?.currency || 'KES' }} {{ formatPrice(ticket.price) }}</p>
                    <p v-if="ticket.description" class="text-sm text-gray-500 mt-1">{{ ticket.description }}</p>
                    <p v-if="ticket.status === 'Sold Out'" class="text-sm text-red-400 mt-1">Sold Out</p>
                  </div>
                  
                  <!-- Quantity Selector -->
                  <div class="flex items-center gap-3">
                    <button 
                      @click="decrementTicket(index)"
                      :disabled="ticketQuantities[index] === 0"
                      class="w-10 h-10 bg-[#305F64] hover:bg-[#305F64]/80 disabled:bg-gray-700 disabled:cursor-not-allowed text-white rounded flex items-center justify-center transition-colors"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                      </svg>
                    </button>
                    
                    <span class="w-12 text-center text-xl font-semibold text-white">{{ ticketQuantities[index] }}</span>
                    
                    <button 
                      @click="incrementTicket(index)"
                      :disabled="ticketQuantities[index] >= (ticket.quantity || 10)"
                      class="w-10 h-10 bg-[#305F64] hover:bg-[#305F64]/80 disabled:bg-gray-700 disabled:cursor-not-allowed text-white rounded flex items-center justify-center transition-colors"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Total Section -->
              <div class="mt-6 pt-6 border-t border-gray-700">
                <div class="flex justify-between items-center mb-2">
                  <span class="text-gray-400">Total</span>
                  <span class="text-2xl font-bold text-white">KES. {{ formatPrice(totalAmount) }}</span>
                </div>
                
                <!-- Tickets Summary -->
                <div class="mb-4">
                  <p class="text-sm text-gray-400 mb-2">Tickets</p>
                  <div v-for="(ticket, index) in ticketTypes" :key="index" v-if="ticketQuantities[index] > 0" class="flex justify-between text-sm mb-1">
                    <span class="text-gray-300">{{ ticketQuantities[index] }} x {{ ticket.name }}</span>
                    <span class="text-gray-300">KES {{ formatPrice(ticket.price * ticketQuantities[index]) }}</span>
                  </div>
                </div>
                
                <!-- Discounts & Fees -->
                <div class="mb-4">
                  <p class="text-sm text-gray-400 mb-2">Discounts & Fees</p>
                  <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-300">Processing fees</span>
                    <span class="text-gray-300">0</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-300">Applied discount (0 %)</span>
                    <span class="text-gray-300">KES. 0</span>
                  </div>
                </div>

                <!-- Purchase Button -->
                <button 
                  @click="handlePurchase"
                  :disabled="totalTickets === 0"
                  class="w-full py-3 bg-[#FDB813] hover:bg-[#FDB813]/90 disabled:bg-gray-700 disabled:cursor-not-allowed text-black font-bold rounded-lg transition-colors text-lg"
                >
                  Purchase tickets
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Event Poster -->
        <div class="lg:col-span-1">
          <div class="sticky top-4">
            <img 
              v-if="event.media?.cover_image"
              :src="event.media.cover_image" 
              :alt="event.title"
              class="w-full rounded-xl shadow-2xl"
            />
            <div v-else class="w-full h-[500px] bg-gradient-to-br from-[#305F64] to-[#253345] rounded-xl"></div>
          </div>
        </div>
      </div>

      <!-- Similar Events Section -->
      <div v-if="similarEvents && similarEvents.length > 0" class="mt-12">
        <h2 class="text-2xl font-bold text-white mb-2">FUN EVENTS</h2>
        <p class="text-gray-400 mb-6">Events near you</p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div 
            v-for="similarEvent in similarEvents.slice(0, 3)" 
            :key="similarEvent.id"
            class="bg-[#253345] rounded-lg overflow-hidden cursor-pointer hover:bg-[#2a3b4f] transition-colors"
            @click="goToEvent(similarEvent)"
          >
            <div class="h-48 bg-gray-800">
              <img 
                v-if="similarEvent.cover_image_url"
                :src="similarEvent.cover_image_url" 
                :alt="similarEvent.title"
                class="w-full h-full object-cover"
              />
            </div>
            <div class="p-4">
              <h3 class="font-semibold text-white mb-2">{{ similarEvent.title }}</h3>
              <p class="text-sm text-[#FDB813]">{{ formatDate(similarEvent.event_date) }}</p>
              <p class="text-sm text-gray-400">{{ similarEvent.venue_name }}</p>
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
import { ref, computed, onMounted } from 'vue'
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

// Initialize ticket quantities
const ticketTypes = computed(() => {
  return props.event.pricing?.ticket_types || []
})

// Initialize quantities to 0
onMounted(() => {
  ticketQuantities.value = new Array(ticketTypes.value.length).fill(0)
  loadSimilarEvents()
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

const handlePurchase = () => {
  if (totalTickets.value > 0) {
    showCheckout.value = true
  }
}

const handleCheckoutComplete = (bookingId) => {
  // Redirect to booking confirmation
  window.location.href = `/booking/confirmation/${bookingId}`
}

const goToEvent = (event) => {
  window.location.href = `/listings/${event.slug || event.id}`
}

const loadSimilarEvents = async () => {
  try {
    const response = await fetch(`/api/events?filter[category_id]=${props.event.category?.id}&filter[exclude]=${props.event.id}&limit=3`)
    const data = await response.json()
    if (data.success) {
      similarEvents.value = data.data.events
    }
  } catch (error) {
    console.error('Error loading similar events:', error)
  }
}
</script>

<style scoped>
/* Custom styles for the black theme */
</style>