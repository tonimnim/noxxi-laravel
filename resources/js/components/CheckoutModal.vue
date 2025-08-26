<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/50" @click="$emit('close')"></div>
    
    <!-- Modal -->
    <div class="relative min-h-screen flex items-center justify-center p-4">
      <div class="relative bg-white rounded-xl max-w-lg w-full shadow-2xl">
        <!-- Close Button -->
        <button 
          @click="$emit('close')"
          class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 z-10"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>

        <!-- Modal Content -->
        <div class="p-6">
          <!-- Event Title -->
          <div class="mb-4">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">EVENT</p>
            <h2 class="text-lg font-semibold">{{ event.title }}</h2>
          </div>

          <!-- Tickets Section -->
          <div class="mb-3">
            <p class="text-sm font-medium text-gray-700 mb-2">Tickets</p>
            <div class="flex justify-between items-center">
              <span class="text-gray-600">{{ totalTickets }} x {{ ticketDescription }}</span>
              <span class="font-medium">{{ event.pricing?.currency || 'KES' }} {{ formatPrice(totalAmount) }}</span>
            </div>
          </div>

          <!-- Discounts & Fees -->
          <div class="mb-3">
            <p class="text-sm font-medium text-gray-700 mb-2">Discounts & Fees</p>
            <div class="space-y-1">
              <div class="flex justify-between items-center">
                <span class="text-gray-600 text-sm">Processing fees</span>
                <span class="text-sm">{{ formatPrice(processingFeeAmount) }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-600 text-sm">Applied discount (0 %)</span>
                <span class="text-sm">KES. 0</span>
              </div>
            </div>
          </div>

          <!-- Total -->
          <div class="py-3 mb-4 border-t">
            <div class="flex justify-between items-center">
              <span class="font-semibold">TOTAL</span>
              <span class="text-xl font-bold">KES. {{ formatPrice(totalWithFees) }}</span>
            </div>
          </div>

          <!-- Payment Methods -->
          <div class="mb-6">
            <p class="text-sm font-medium text-gray-700 mb-3">Pay with</p>
            <div class="flex gap-2">
              <button
                v-for="method in paymentMethods"
                :key="method.id"
                @click="selectedPaymentMethod = method.id"
                :class="[
                  'flex-1 p-3 border rounded-lg transition-all flex items-center justify-center relative',
                  selectedPaymentMethod === method.id 
                    ? 'border-green-500 bg-white' 
                    : 'border-gray-200 hover:border-gray-300'
                ]"
              >
                <!-- M-Pesa Logo -->
                <div v-if="method.id === 'mpesa'" class="text-green-600 font-bold text-sm">MPESA</div>
                
                <!-- Visa/Mastercard Logos -->
                <div v-if="method.id === 'card'" class="flex items-center gap-2">
                  <span class="text-sm font-medium">VISA / Mastercard</span>
                </div>
                
                <!-- Apple Pay Logo -->
                <div v-if="method.id === 'apple'" class="flex items-center">
                  <span class="text-sm font-medium">Apple Pay</span>
                </div>
                
                <!-- Checkmark -->
                <svg v-if="selectedPaymentMethod === method.id" class="absolute top-1 right-1 w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex gap-3">
            <button
              @click="$emit('close')"
              class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              @click="handleCompletePayment"
              :disabled="!canProceed || processing"
              class="flex-1 px-6 py-2.5 bg-[#FDB813] text-black rounded-lg text-sm font-semibold hover:bg-[#FDB813]/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ processing ? 'Processing...' : 'Complete payment' }}
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
  selectedTickets: {
    type: Array,
    required: true
  },
  totalAmount: {
    type: Number,
    required: true
  }
})

// Emits
const emit = defineEmits(['close', 'complete'])

// State
const selectedPaymentMethod = ref('mpesa')
const contactDetails = ref({
  phone: '254700000000', // Default phone for now
  email: 'user@example.com' // Default email for now
})
const agreedToTerms = ref(true) // Auto-agreed since we removed the checkbox
const processing = ref(false)

// Payment methods
const paymentMethods = [
  { id: 'mpesa', name: 'MPESA' },
  { id: 'card', name: 'VISA / Mastercard' },
  { id: 'apple', name: 'Apple Pay' }
]

// Computed
const processingFeeAmount = computed(() => {
  return 0 // No processing fee shown in the design
})

const totalWithFees = computed(() => {
  return props.totalAmount // No additional fees
})

const totalTickets = computed(() => {
  return props.selectedTickets.reduce((sum, ticket) => sum + ticket.quantity, 0)
})

const ticketDescription = computed(() => {
  if (props.selectedTickets.length === 1) {
    return props.selectedTickets[0].name
  }
  return 'Mixed Tickets'
})

const canProceed = computed(() => {
  return selectedPaymentMethod.value // Only need payment method selected
})

// Methods
const formatPrice = (price) => {
  return new Intl.NumberFormat().format(Math.round(price || 0))
}

const handleCompletePayment = async () => {
  if (!canProceed.value) return

  processing.value = true

  try {
    // Create booking using web route (session-based auth)
    const response = await fetch('/web/bookings', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include',
      body: JSON.stringify({
        event_id: props.event.id,
        ticket_types: props.selectedTickets.map(ticket => ({
          name: ticket.name,
          type: 'regular',
          quantity: ticket.quantity,
          price: ticket.price
        })),
        payment_method: selectedPaymentMethod.value,
        total_amount: totalWithFees.value
      })
    })

    const data = await response.json()

    if (data.success) {
      // All payment methods go through Paystack
      await initializePaystackPayment(data.data.booking_id)
    } else {
      alert('Error creating booking: ' + (data.message || 'Please try again'))
    }
  } catch (error) {
    console.error('Checkout error:', error)
    alert('An error occurred. Please try again.')
  } finally {
    processing.value = false
  }
}

const initializePaystackPayment = async (bookingId) => {
  try {
    const response = await fetch('/web/payments/paystack/initialize', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include',
      body: JSON.stringify({
        booking_id: bookingId
      })
    })

    const data = await response.json()
    
    if (data.success && data.data.authorization_url) {
      // Redirect to Paystack payment page
      window.location.href = data.data.authorization_url
    } else {
      alert('Error initiating payment: ' + (data.message || 'Please try again'))
    }
  } catch (error) {
    alert('Failed to initialize payment. Please try again.')
  }
}

// M-Pesa is handled through Paystack, no separate function needed

const pollPaymentStatus = async (bookingId) => {
  const maxAttempts = 30
  let attempts = 0
  
  const checkStatus = setInterval(async () => {
    attempts++
    
    try {
      const response = await fetch(`/api/bookings/${bookingId}/status`)
      const data = await response.json()
      
      if (data.data?.payment_status === 'paid') {
        clearInterval(checkStatus)
        emit('complete', bookingId)
      }
    } catch (error) {
      console.error('Error checking payment status:', error)
    }
    
    if (attempts >= maxAttempts) {
      clearInterval(checkStatus)
      alert('Payment verification timeout. Please check your booking history.')
    }
  }, 2000)
}
</script>