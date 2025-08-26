<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <app-header />
    
    <!-- Confirmation Container -->
    <div class="max-w-4xl mx-auto px-4 py-12">
      <!-- Success Message -->
      <div class="bg-white rounded-lg shadow-lg p-8 text-center mb-8">
        <div class="mb-6">
          <svg class="w-20 h-20 mx-auto text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        
        <h1 class="text-3xl font-bold mb-2">Booking Confirmed!</h1>
        <p class="text-gray-600 mb-6">Your tickets have been successfully purchased.</p>
        
        <div v-if="booking" class="bg-gray-50 rounded-lg p-6 mb-6">
          <p class="text-sm text-gray-600 mb-2">Booking Reference</p>
          <p class="text-2xl font-bold font-mono">{{ booking.reference_number }}</p>
        </div>
        
        <p class="text-sm text-gray-600">
          A confirmation email has been sent to <strong>{{ booking?.email }}</strong> with your tickets.
        </p>
      </div>

      <!-- Tickets Section -->
      <div v-if="booking && booking.tickets" class="space-y-6">
        <h2 class="text-2xl font-bold mb-4">Your Tickets</h2>
        
        <div v-for="ticket in booking.tickets" :key="ticket.id" class="bg-white rounded-lg shadow-md overflow-hidden">
          <div class="flex">
            <!-- QR Code Section -->
            <div class="bg-gray-100 p-6 flex items-center justify-center">
              <div class="text-center">
                <div class="bg-white p-4 rounded-lg mb-2">
                  <p class="text-gray-500 text-sm">QR code will be available in My Account</p>
                </div>
                <p class="text-xs text-gray-600">{{ ticket.ticket_code }}</p>
              </div>
            </div>
            
            <!-- Ticket Details -->
            <div class="flex-1 p-6">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-xl font-semibold mb-1">{{ booking.event.title }}</h3>
                  <p class="text-gray-600">{{ ticket.ticket_type }}</p>
                </div>
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                  Valid
                </span>
              </div>
              
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <p class="text-gray-500">Date</p>
                  <p class="font-medium">{{ formatDate(booking.event.event_date) }}</p>
                </div>
                <div>
                  <p class="text-gray-500">Time</p>
                  <p class="font-medium">{{ formatTime(booking.event.event_date) }}</p>
                </div>
                <div>
                  <p class="text-gray-500">Venue</p>
                  <p class="font-medium">{{ booking.event.venue_name }}</p>
                </div>
                <div>
                  <p class="text-gray-500">Price</p>
                  <p class="font-medium">{{ booking.event.currency }} {{ formatPrice(ticket.price) }}</p>
                </div>
              </div>
              
              <div class="mt-4 pt-4 border-t">
                <p class="text-xs text-gray-500">
                  View your ticket with QR code in My Account section. Screenshot or save for offline access.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="mt-8 flex gap-4 justify-center">
        <button
          @click="downloadTickets"
          class="px-6 py-3 bg-[#305F64] text-white rounded-lg font-medium hover:bg-[#305F64]/90 transition-colors"
        >
          Download Tickets (PDF)
        </button>
        <button
          @click="sendToEmail"
          class="px-6 py-3 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors"
        >
          Send to Email
        </button>
        <a
          href="/"
          class="px-6 py-3 border border-gray-300 rounded-lg font-medium hover:bg-gray-50 transition-colors inline-block"
        >
          Back to Home
        </a>
      </div>

      <!-- Important Information -->
      <div class="mt-12 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
        <h3 class="font-semibold text-yellow-800 mb-2">Important Information</h3>
        <ul class="text-sm text-yellow-700 space-y-1">
          <li>• Please arrive at least 30 minutes before the event starts</li>
          <li>• Bring a valid ID for verification at the venue</li>
          <li>• This ticket is non-refundable and non-transferable</li>
          <li>• Keep your QR code secure - do not share it publicly</li>
        </ul>
      </div>
    </div>
    
    <!-- Footer -->
    <app-footer />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppHeader from './AppHeader.vue'
import AppFooter from './AppFooter.vue'

// Props
const props = defineProps({
  bookingId: {
    type: String,
    required: true
  }
})

// State
const booking = ref(null)
const loading = ref(true)

// Methods
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatTime = (dateString) => {
  return new Date(dateString).toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true
  })
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  }).format(price)
}

const loadBookingDetails = async () => {
  try {
    loading.value = true
    const response = await fetch(`/web/bookings/${props.bookingId}`, {
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include'
    })
    
    const data = await response.json()
    
    if (data.success) {
      booking.value = data.data.booking
    } else {
      console.error('Error loading booking:', data.message)
    }
  } catch (error) {
    console.error('Error loading booking details:', error)
  } finally {
    loading.value = false
  }
}

const downloadTickets = async () => {
  try {
    const response = await fetch(`/api/bookings/${props.bookingId}/download`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    
    if (response.ok) {
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `tickets-${booking.value.reference_number}.pdf`
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)
    } else {
      alert('Error downloading tickets. Please try again.')
    }
  } catch (error) {
    console.error('Error downloading tickets:', error)
    alert('Failed to download tickets. Please try again.')
  }
}

const sendToEmail = async () => {
  try {
    const response = await fetch(`/api/bookings/${props.bookingId}/resend-email`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })
    
    const data = await response.json()
    
    if (data.success) {
      alert('Tickets have been sent to your email!')
    } else {
      alert('Error sending email: ' + data.message)
    }
  } catch (error) {
    console.error('Error sending email:', error)
    alert('Failed to send email. Please try again.')
  }
}

// Lifecycle
onMounted(() => {
  loadBookingDetails()
})
</script>