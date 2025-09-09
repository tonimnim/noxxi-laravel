<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <app-header />
    
    <!-- Page Content -->
    <div class="max-w-7xl mx-auto px-4 md:px-8 lg:px-12 xl:px-20 pb-8 pt-32 md:pt-36 lg:pt-40">
      <!-- Page Title -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-[#223338]">All Sports</h1>
      </div>
      
      <!-- Events Grid - Larger cards with fewer columns -->
      <div v-if="events.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 md:gap-8">
        <div 
          v-for="event in events" 
          :key="event.id"
          class="group cursor-pointer"
          @click="goToEventDetails(event)"
        >
          <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-xl transition-all duration-300">
            <!-- Event Image -->
            <div class="relative h-40 sm:h-48 lg:h-44 xl:h-48 overflow-hidden">
              <img 
                v-if="event.cover_image_url"
                :src="event.cover_image_url" 
                :alt="event.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              >
              <div v-else class="w-full h-full bg-gradient-to-br from-green-500 to-teal-600"></div>

              <!-- Price Badge -->
              <div class="absolute top-3 right-3">
                <span class="px-2 lg:px-3 py-0.5 lg:py-1 bg-[#305F64]/90 backdrop-blur-sm text-white rounded-full text-xs lg:text-sm font-medium">
                  {{ formatPrice(event) }}
                </span>
              </div>
            </div>

            <!-- Event Details -->
            <div class="p-4 lg:p-4 xl:p-5">
              <!-- Title -->
              <h3 class="font-bold text-base lg:text-lg xl:text-xl text-[#223338] line-clamp-2 group-hover:text-[#305F64] transition-colors">
                {{ event.title }}
              </h3>

              <!-- Date -->
              <div class="text-sm text-gray-600 mb-1">
                {{ formatDate(event.event_date) }}
              </div>

              <!-- Location -->
              <div class="flex items-center gap-2 text-sm text-gray-600 mb-3">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="line-clamp-1">{{ event.venue_address }}</span>
              </div>

              <!-- Description Extract -->
              <p class="text-sm text-gray-700 line-clamp-2">
                {{ event.description ? (event.description.length > 100 ? event.description.substring(0, 100) + '...' : event.description) : '' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading && events.length === 0" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-[#305F64]"></div>
        <p class="mt-4 text-gray-600">Loading events...</p>
      </div>

      <!-- No Events State -->
      <div v-if="!loading && events.length === 0" class="text-center py-12">
        <p class="text-gray-600">No events available at the moment.</p>
      </div>

      <!-- Load More Button -->
      <div v-if="hasMore && events.length > 0" class="text-center mt-10">
        <button 
          @click="loadMore"
          :disabled="loading"
          class="inline-flex items-center gap-2 bg-[#305F64] text-white px-8 py-3 rounded-lg font-medium hover:opacity-90 transition-opacity disabled:opacity-50"
        >
          <span v-if="loading">Loading...</span>
          <span v-else>Load more events</span>
          <svg v-if="!loading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Footer -->
    <AppFooter />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppHeader from '../layout/AppHeader.vue'
import AppFooter from '../layout/AppFooter.vue'

// State
const events = ref([])
const loading = ref(false)
const page = ref(1)
const hasMore = ref(true)

// Fetch events
const fetchEvents = async (loadMore = false) => {
  loading.value = true
  try {
    const response = await fetch(`/api/sports?page=${page.value}&per_page=12`)
    const data = await response.json()
    
    if (data.status === 'success') {
      const eventsData = data.data?.events || []
      const meta = data.data?.meta || {}
      
      if (loadMore) {
        events.value = [...events.value, ...eventsData]
      } else {
        events.value = eventsData
      }
      
      hasMore.value = meta.current_page < meta.last_page
    }
  } catch (error) {
    console.error('Error fetching events:', error)
  } finally {
    loading.value = false
  }
}

// Load more
const loadMore = () => {
  page.value++
  fetchEvents(true)
}

// Format price
const formatPrice = (event) => {
  if (!event.min_price || event.min_price === 0) {
    return 'Free'
  }
  const currency = event.currency || 'USD'
  const price = parseFloat(event.min_price).toLocaleString()
  return `${currency} ${price}`
}

// Format date
const formatDate = (dateString) => {
  const date = new Date(dateString)
  const today = new Date()
  const tomorrow = new Date(today)
  tomorrow.setDate(tomorrow.getDate() + 1)
  
  // Check if today
  if (date.toDateString() === today.toDateString()) {
    return 'Today'
  }
  
  // Check if tomorrow
  if (date.toDateString() === tomorrow.toDateString()) {
    return 'Tomorrow'
  }
  
  // Otherwise return formatted date
  return date.toLocaleDateString('en-US', { 
    weekday: 'short',
    month: 'short', 
    day: 'numeric'
  })
}

// Navigate to event details
const goToEventDetails = (event) => {
  const identifier = event.slug || event.id
  window.location.href = `/listings/${identifier}`
}

onMounted(() => {
  fetchEvents()
})
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>