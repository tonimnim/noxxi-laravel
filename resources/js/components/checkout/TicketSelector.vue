<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="$emit('close')"></div>
    
    <!-- Modal -->
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <!-- Header -->
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 z-10">
          <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">Select Tickets</h2>
            <button 
              @click="$emit('close')"
              class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Content -->
        <div class="px-6 py-4 overflow-y-auto max-h-[60vh]">
          <!-- Event Info -->
          <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-semibold text-gray-900 mb-1">{{ event.title }}</h3>
            <p class="text-sm text-gray-600">{{ event.dates.formatted_date }} at {{ event.dates.formatted_time }}</p>
            <p class="text-sm text-gray-600">{{ event.venue.name }}, {{ event.venue.city }}</p>
          </div>
          
          <!-- Ticket Types -->
          <div class="space-y-4">
            <div 
              v-for="(ticketType, index) in ticketTypes" 
              :key="index"
              class="border border-gray-200 rounded-lg p-4 hover:border-[#305F64] transition-colors"
            >
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                  <h4 class="font-semibold text-gray-900">{{ ticketType.name }}</h4>
                  <p v-if="ticketType.description" class="text-sm text-gray-600 mt-1">{{ ticketType.description }}</p>
                  <p class="text-lg font-bold text-[#305F64] mt-2">
                    {{ event.pricing.currency }} {{ formatNumber(ticketType.price) }}
                  </p>
                </div>
              </div>
              
              <!-- Quantity Selector -->
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Select quantity:</span>
                <div class="flex items-center gap-3">
                  <button 
                    @click="decrementQuantity(index)"
                    :disabled="quantities[index] === 0"
                    class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                    </svg>
                  </button>
                  <span class="w-12 text-center font-semibold">{{ quantities[index] || 0 }}</span>
                  <button 
                    @click="incrementQuantity(index)"
                    :disabled="quantities[index] >= (ticketType.max_per_order || 10)"
                    class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- No tickets message -->
          <div v-if="!ticketTypes || ticketTypes.length === 0" class="text-center py-8">
            <p class="text-gray-500">No tickets available for this event.</p>
          </div>
        </div>
        
        <!-- Footer -->
        <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4">
          <div class="flex items-center justify-between mb-4">
            <div>
              <p class="text-sm text-gray-600">Total tickets: <span class="font-semibold">{{ totalTickets }}</span></p>
              <p class="text-xl font-bold text-[#305F64]">
                Total: {{ event.pricing.currency }} {{ formatNumber(totalAmount) }}
              </p>
            </div>
            <button 
              @click="proceedToCheckout"
              :disabled="totalTickets === 0"
              class="px-6 py-3 bg-[#305F64] text-white font-semibold rounded-lg hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed transition-opacity"
            >
              Proceed to Checkout
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Props
const props = defineProps({
  event: {
    type: Object,
    required: true
  },
  ticketTypes: {
    type: Array,
    required: true
  }
})

// Emits
const emit = defineEmits(['close', 'proceed-to-checkout'])

// State
const quantities = ref(props.ticketTypes.map(() => 0))

// Computed
const totalTickets = computed(() => {
  return quantities.value.reduce((sum, qty) => sum + qty, 0)
})

const totalAmount = computed(() => {
  return quantities.value.reduce((sum, qty, index) => {
    return sum + (qty * props.ticketTypes[index].price)
  }, 0)
})

// Methods
const incrementQuantity = (index) => {
  const max = props.ticketTypes[index].max_per_order || 10
  if (quantities.value[index] < max) {
    quantities.value[index]++
  }
}

const decrementQuantity = (index) => {
  if (quantities.value[index] > 0) {
    quantities.value[index]--
  }
}

const formatNumber = (num) => {
  return new Intl.NumberFormat().format(num)
}

const proceedToCheckout = () => {
  if (totalTickets.value === 0) {
    alert('Please select at least one ticket')
    return
  }
  
  // Prepare selected tickets data
  const selectedTickets = props.ticketTypes
    .map((ticket, index) => ({
      ...ticket,
      quantity: quantities.value[index]
    }))
    .filter(ticket => ticket.quantity > 0)
  
  emit('proceed-to-checkout', {
    tickets: selectedTickets,
    totalAmount: totalAmount.value,
    totalQuantity: totalTickets.value
  })
}
</script>