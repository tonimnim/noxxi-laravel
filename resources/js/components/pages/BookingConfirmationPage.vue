<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <app-header />
    
    <!-- Confirmation Container -->
    <div class="flex-1 flex items-center justify-center px-4">
      <div class="w-full max-w-2xl">
        <!-- Success Message -->
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
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
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppHeader from '../layout/AppHeader.vue'

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