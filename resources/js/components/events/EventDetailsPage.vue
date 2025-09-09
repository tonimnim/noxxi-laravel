<template>
  <div class="h-screen overflow-hidden bg-gray-50">
    <!-- Header -->
    <app-header :force-white-text="true" />
    
    <!-- Event Details Section -->
    <event-details-clean 
      v-if="event"
      :event="event"
    />
    
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-96">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#305F64]"></div>
    </div>
    
    <!-- Error State -->
    <div v-if="error" class="max-w-7xl mx-auto px-4 py-16">
      <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <h3 class="text-red-800 font-semibold mb-2">Error Loading Event</h3>
        <p class="text-red-600">{{ error }}</p>
        <a href="/" class="inline-block mt-4 text-[#305F64] hover:underline">← Back to Home</a>
      </div>
    </div>
    
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppHeader from '../layout/AppHeader.vue'
import EventDetailsClean from './EventDetailsClean.vue'

// Props from server
const props = defineProps({
  initialEvent: {
    type: Object,
    default: null
  }
})

// State
const event = ref(props.initialEvent)
const loading = ref(!props.initialEvent)
const error = ref(null)
const showTicketSelector = ref(false)

// Methods
const handleSelectTickets = () => {
  if (event.value.availability.is_sold_out) {
    alert('Sorry, this event is sold out!')
    return
  }
  showTicketSelector.value = true
}

const handleProceedToCheckout = (selectedTickets) => {
  // Store selected tickets in session storage
  sessionStorage.setItem('selectedTickets', JSON.stringify(selectedTickets))
  sessionStorage.setItem('eventId', event.value.id)
  
  // Redirect to checkout
  window.location.href = `/checkout/${event.value.id}`
}

// Load event if not provided from server
onMounted(() => {
  if (!event.value) {
    // This shouldn't happen with SSR, but as fallback
    const eventId = window.location.pathname.split('/').pop()
    loadEvent(eventId)
  }
})

const loadEvent = async (eventId) => {
  try {
    loading.value = true
    const response = await fetch(`/api/events/${eventId}`)
    const data = await response.json()
    
    if (data.success) {
      event.value = formatEventData(data.data.event)
    } else {
      error.value = data.message || 'Failed to load event'
    }
  } catch (err) {
    error.value = 'Failed to load event details. Please try again.'
    console.error('Error loading event:', err)
  } finally {
    loading.value = false
  }
}

const formatEventData = (apiEvent) => {
  // Transform API response to match expected format
  return {
    id: apiEvent.id,
    title: apiEvent.title,
    slug: apiEvent.slug,
    description: apiEvent.description,
    category: apiEvent.category,
    organizer: {
      id: apiEvent.organizer?.id,
      name: apiEvent.organizer?.business_name,
      logo: apiEvent.organizer?.business_logo_url,
      description: apiEvent.organizer?.business_description,
      is_verified: apiEvent.organizer?.is_verified,
    },
    venue: {
      name: apiEvent.venue_name,
      address: apiEvent.venue_address,
      city: apiEvent.city,
      latitude: apiEvent.latitude,
      longitude: apiEvent.longitude,
    },
    dates: {
      event_date: apiEvent.event_date,
      end_date: apiEvent.end_date,
      formatted_date: new Date(apiEvent.event_date).toLocaleDateString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      }),
      formatted_time: new Date(apiEvent.event_date).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
      }),
    },
    pricing: {
      min_price: apiEvent.min_price,
      max_price: apiEvent.max_price,
      currency: apiEvent.currency,
      ticket_types: apiEvent.ticket_types,
    },
    availability: {
      capacity: apiEvent.capacity,
      tickets_sold: apiEvent.tickets_sold,
      available_tickets: apiEvent.available_tickets,
      is_sold_out: apiEvent.is_sold_out,
    },
    media: {
      cover_image: apiEvent.cover_image_url,
      images: apiEvent.images,
      video_url: apiEvent.video_url,
    },
    policies: {
      age_restriction: apiEvent.age_restriction,
      terms_conditions: apiEvent.terms_conditions,
      refund_policy: apiEvent.refund_policy,
    },
    tags: apiEvent.tags,
    is_featured: apiEvent.featured,
    is_upcoming: apiEvent.is_upcoming,
  }
}
</script>