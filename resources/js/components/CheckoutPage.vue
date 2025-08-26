<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <app-header />
    
    <!-- Checkout Container -->
    <div class="max-w-6xl mx-auto px-4 py-8">
      <div class="flex gap-8">
        <!-- Left Column - Tickets & Payment -->
        <div class="flex-1">
          <!-- Event Info Card -->
          <div class="bg-white rounded-lg p-6 mb-6" v-if="event">
            <h2 class="text-2xl font-bold mb-2">{{ event.title }}</h2>
            <div class="text-gray-600">
              <p>{{ event.venue.name }} • {{ event.venue.city }}</p>
              <p>{{ event.dates.formatted_date }} at {{ event.dates.formatted_time }}</p>
            </div>
          </div>

          <!-- Tickets Section -->
          <div class="bg-white rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">TICKETS</h3>
            
            <div v-for="ticket in selectedTickets" :key="ticket.type" class="mb-4">
              <div class="flex justify-between items-center">
                <div>
                  <h4 class="font-medium">{{ ticket.name }}</h4>
                  <p class="text-gray-600">{{ event?.currency }} {{ formatPrice(ticket.price) }}</p>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-gray-600">{{ ticket.quantity }} x {{ ticket.name }}</span>
                  <span class="font-semibold">{{ event?.currency }} {{ formatPrice(ticket.price * ticket.quantity) }}</span>
                </div>
              </div>
            </div>

            <!-- Discounts & Fees -->
            <div class="border-t pt-4 mt-4">
              <div class="flex justify-between mb-2">
                <span class="text-gray-600">Processing fees</span>
                <span>{{ event?.currency }} {{ formatPrice(processingFee) }}</span>
              </div>
              <div v-if="discount > 0" class="flex justify-between mb-2">
                <span class="text-gray-600">Applied discount ({{ discountPercentage }}%)</span>
                <span class="text-green-600">-{{ event?.currency }} {{ formatPrice(discount) }}</span>
              </div>
            </div>

            <!-- Total -->
            <div class="border-t pt-4 mt-4">
              <div class="flex justify-between items-center">
                <span class="text-lg font-semibold">TOTAL</span>
                <span class="text-2xl font-bold">{{ event?.currency }} {{ formatPrice(totalAmount) }}</span>
              </div>
            </div>
          </div>

          <!-- Payment Methods -->
          <div class="bg-white rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">Pay with</h3>
            
            <div class="grid grid-cols-2 gap-4">
              <button
                v-for="method in paymentMethods"
                :key="method.id"
                @click="selectedPaymentMethod = method.id"
                :class="[
                  'p-4 border-2 rounded-lg flex items-center justify-center transition-all',
                  selectedPaymentMethod === method.id 
                    ? 'border-[#305F64] bg-[#305F64]/5' 
                    : 'border-gray-200 hover:border-gray-300'
                ]"
              >
                <div class="flex items-center gap-3">
                  <i v-if="method.icon" :class="method.icon"></i>
                  <span class="font-medium">{{ method.name }}</span>
                  <svg v-if="selectedPaymentMethod === method.id" class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                </div>
              </button>
            </div>
          </div>

          <!-- Contact Details -->
          <div class="bg-white rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Contact details</h3>
            
            <form @submit.prevent="handleCheckout">
              <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Phone number*</label>
                  <input
                    v-model="contactDetails.phone"
                    type="tel"
                    required
                    placeholder="2547xxxxxxxx"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#305F64]"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Email*</label>
                  <input
                    v-model="contactDetails.email"
                    type="email"
                    required
                    placeholder="james@example.com"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#305F64]"
                  />
                </div>
              </div>

              <!-- Terms and Conditions -->
              <div class="mb-6">
                <h4 class="font-medium mb-2">Terms and conditions</h4>
                <div class="text-sm text-gray-600 space-y-2">
                  <p>Ticket(s) cannot be cancelled or refunded after purchase.</p>
                  <p>Change of ticket is not allowed.</p>
                  <p>By purchasing a ticket for this event, you consent to being recorded for data analysis, promotional, and archival purposes.</p>
                </div>
                <div class="mt-3">
                  <label class="flex items-start">
                    <input
                      v-model="agreedToTerms"
                      type="checkbox"
                      required
                      class="mt-1 mr-2"
                    />
                    <span class="text-sm">I agree to the Terms and Conditions and Privacy Policy.</span>
                  </label>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex gap-4">
                <button
                  type="button"
                  @click="handleCancel"
                  class="px-6 py-3 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="!agreedToTerms || !selectedPaymentMethod || processing"
                  class="flex-1 px-6 py-3 bg-[#F2A227] text-white rounded-lg font-medium hover:bg-[#F2A227]/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ processing ? 'Processing...' : 'Complete payment' }}
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Right Column - Order Summary -->
        <div class="w-96">
          <div class="bg-white rounded-lg p-6 sticky top-4">
            <h3 class="text-lg font-semibold mb-4">Order Summary</h3>
            
            <!-- Event Image -->
            <div v-if="event" class="mb-4">
              <img 
                :src="event.media.cover_image" 
                :alt="event.title"
                class="w-full h-48 object-cover rounded-lg"
              />
            </div>

            <!-- Summary Details -->
            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Subtotal</span>
                <span>{{ event?.currency }} {{ formatPrice(subtotal) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Processing Fee</span>
                <span>{{ event?.currency }} {{ formatPrice(processingFee) }}</span>
              </div>
              <div v-if="discount > 0" class="flex justify-between">
                <span class="text-gray-600">Discount</span>
                <span class="text-green-600">-{{ event?.currency }} {{ formatPrice(discount) }}</span>
              </div>
              <div class="border-t pt-3">
                <div class="flex justify-between font-semibold text-lg">
                  <span>Total</span>
                  <span>{{ event?.currency }} {{ formatPrice(totalAmount) }}</span>
                </div>
              </div>
            </div>

            <!-- Ticket Count -->
            <div class="mt-6 p-3 bg-gray-50 rounded-lg">
              <div class="text-center">
                <span class="text-2xl font-bold">{{ totalTickets }}</span>
                <p class="text-gray-600">Ticket{{ totalTickets > 1 ? 's' : '' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Footer -->
    <app-footer />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppHeader from './AppHeader.vue'
import AppFooter from './AppFooter.vue'

// Props
const props = defineProps({
  eventId: {
    type: String,
    required: true
  }
})

// State
const event = ref(null)
const selectedTickets = ref([])
const selectedPaymentMethod = ref('mpesa')
const contactDetails = ref({
  phone: '',
  email: ''
})
const agreedToTerms = ref(false)
const processing = ref(false)
const discountPercentage = ref(0)

// Payment methods
const paymentMethods = [
  { id: 'mpesa', name: 'MPESA', icon: null },
  { id: 'visa', name: 'VISA / Mastercard', icon: null },
  { id: 'paystack', name: 'Paystack', icon: null },
  { id: 'bank', name: 'Bank Transfer', icon: null }
]

// Computed
const subtotal = computed(() => {
  return selectedTickets.value.reduce((sum, ticket) => {
    return sum + (ticket.price * ticket.quantity)
  }, 0)
})

const processingFee = computed(() => {
  // No processing fee - customer pays exact ticket price
  return 0
})

const discount = computed(() => {
  return subtotal.value * (discountPercentage.value / 100)
})

const totalAmount = computed(() => {
  return subtotal.value - discount.value
})

const totalTickets = computed(() => {
  return selectedTickets.value.reduce((sum, ticket) => sum + ticket.quantity, 0)
})

// Methods
const formatPrice = (price) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(price)
}

const loadEventDetails = async () => {
  try {
    const response = await fetch(`/api/events/${props.eventId}`)
    const data = await response.json()
    
    if (data.success) {
      event.value = data.data.event
    }
  } catch (error) {
    console.error('Error loading event:', error)
  }
}

const loadSelectedTickets = () => {
  const stored = sessionStorage.getItem('selectedTickets')
  if (stored) {
    selectedTickets.value = JSON.parse(stored)
  } else {
    // Redirect back if no tickets selected
    window.location.href = `/listings/${props.eventId}`
  }
}

const handleCheckout = async () => {
  if (!agreedToTerms.value || !selectedPaymentMethod.value) {
    return
  }

  processing.value = true

  try {
    const response = await fetch('/api/bookings', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        event_id: props.eventId,
        tickets: selectedTickets.value,
        payment_method: selectedPaymentMethod.value,
        contact_details: contactDetails.value,
        total_amount: totalAmount.value
      })
    })

    const data = await response.json()

    if (data.success) {
      // Clear session storage
      sessionStorage.removeItem('selectedTickets')
      sessionStorage.removeItem('eventId')
      
      // Handle payment initialization based on method
      if (selectedPaymentMethod.value === 'mpesa') {
        // Initialize M-Pesa payment
        await initializeMpesaPayment(data.data.booking_id)
      } else if (selectedPaymentMethod.value === 'visa' || selectedPaymentMethod.value === 'paystack') {
        // Initialize Paystack payment
        await initializePaystackPayment(data.data.booking_id)
      } else {
        // Redirect to confirmation page
        window.location.href = `/booking/confirmation/${data.data.booking_id}`
      }
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

const initializeMpesaPayment = async (bookingId) => {
  try {
    const response = await fetch('/api/payments/mpesa/initialize', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        booking_id: bookingId,
        phone_number: contactDetails.value.phone
      })
    })

    const data = await response.json()
    
    if (data.success) {
      // Show STK push notification
      alert('Please check your phone for the M-Pesa payment prompt')
      
      // Poll for payment status
      pollPaymentStatus(bookingId)
    } else {
      alert('Error initiating payment: ' + data.message)
    }
  } catch (error) {
    console.error('M-Pesa initialization error:', error)
    alert('Failed to initialize M-Pesa payment')
  }
}

const initializePaystackPayment = async (bookingId) => {
  // Implement Paystack payment initialization
  // This would typically open Paystack payment modal
  alert('Paystack payment coming soon')
}

const pollPaymentStatus = async (bookingId) => {
  // Poll for payment confirmation
  const maxAttempts = 30 // 30 attempts, 2 seconds each = 1 minute
  let attempts = 0
  
  const checkStatus = setInterval(async () => {
    attempts++
    
    try {
      const response = await fetch(`/api/bookings/${bookingId}/status`)
      const data = await response.json()
      
      if (data.data.payment_status === 'paid') {
        clearInterval(checkStatus)
        window.location.href = `/booking/confirmation/${bookingId}`
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

const handleCancel = () => {
  if (confirm('Are you sure you want to cancel this order?')) {
    sessionStorage.removeItem('selectedTickets')
    sessionStorage.removeItem('eventId')
    window.location.href = `/listings/${props.eventId}`
  }
}

// Lifecycle
onMounted(() => {
  loadEventDetails()
  loadSelectedTickets()
})
</script>