<template>
  <section v-show="isVisible" class="py-16 px-4 md:px-8 lg:px-12 xl:px-20 bg-gray-50">
    <div class="max-w-7xl mx-auto">
      <!-- Section Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h2 class="text-3xl md:text-4xl font-bold text-[#223338]">Events</h2>
          <p class="text-gray-600 mt-2">Upcoming events you don't want to miss</p>
        </div>
        <a href="/events" class="hidden md:inline-flex items-center gap-2 text-[#305F64] font-medium hover:opacity-80 transition-opacity">
          View all events
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </a>
      </div>

      <!-- Events Grid - Responsive 2/3/4 Columns -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-5 lg:gap-6">
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
              <div v-else class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300"></div>
              
              <!-- Date Badge -->
              <div class="absolute top-3 left-3 bg-white rounded-lg p-1.5 lg:p-2 text-center shadow-md">
                <div class="text-[10px] lg:text-xs font-medium text-gray-500 uppercase">{{ getMonth(event.event_date) }}</div>
                <div class="text-base lg:text-lg xl:text-xl font-bold text-[#223338]">{{ getDay(event.event_date) }}</div>
              </div>

              <!-- Price Badge -->
              <div class="absolute top-3 right-3">
                <span class="px-2 lg:px-3 py-0.5 lg:py-1 bg-[#305F64]/90 backdrop-blur-sm text-white rounded-full text-xs lg:text-sm font-medium">
                  {{ formatPrice(event) }}
                </span>
              </div>

              <!-- Category -->
              <div class="absolute bottom-3 left-3">
                <span class="px-2 lg:px-3 py-0.5 lg:py-1 bg-white/90 backdrop-blur-sm rounded-full text-[10px] lg:text-xs font-medium text-[#223338]">
                  {{ event.category?.name || 'Event' }}
                </span>
              </div>
            </div>

            <!-- Event Details -->
            <div class="p-4 lg:p-4 xl:p-5">
              <!-- Title -->
              <h3 class="font-bold text-base lg:text-lg xl:text-xl text-[#223338] mb-2 line-clamp-2 group-hover:text-[#305F64] transition-colors">
                {{ event.title }}
              </h3>

              <!-- Time & Location -->
              <div class="space-y-2 mb-4">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  <span>{{ formatTime(event.event_date) }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  </svg>
                  <span class="line-clamp-1">{{ event.venue_name || event.city }}</span>
                </div>
              </div>

              <!-- Organizer & Action -->
              <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-gray-200"></div>
                  <span class="text-sm text-gray-600">{{ event.organizer?.business_name || 'Organizer' }}</span>
                </div>
                <button class="p-2 rounded-full hover:bg-gray-100 transition-colors">
                  <svg class="w-5 h-5 text-[#305F64]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Load More Button -->
      <div class="text-center mt-10">
        <button 
          @click="loadMore"
          v-if="hasMore"
          class="inline-flex items-center gap-2 bg-[#305F64] text-white px-8 py-3 rounded-lg font-medium hover:opacity-90 transition-opacity"
        >
          Load more events
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

// State
const events = ref([])
const page = ref(1)
const hasMore = ref(true)
const loading = ref(false)
const isVisible = ref(true)
const searchQuery = ref('')

// Fetch events
const fetchEvents = async (loadingMore = false) => {
  if (loading.value) return
  loading.value = true

  try {
    const response = await fetch(`/api/events?page=${page.value}&per_page=12`)
    const data = await response.json()
    
    if (data.status === 'success') {
      // API returns data.data.events for the events array
      const eventsData = data.data?.events || []
      const meta = data.data?.meta || {}
      
      if (loadingMore) {
        events.value = [...events.value, ...eventsData]
      } else {
        events.value = eventsData
      }
      
      // Check if there are more pages
      hasMore.value = meta.current_page < meta.last_page
    }
  } catch (error) {
    console.error('Error fetching events:', error)
  } finally {
    loading.value = false
  }
}

// Load more events
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

// Get month from date
const getMonth = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { month: 'short' })
}

// Get day from date
const getDay = (dateString) => {
  const date = new Date(dateString)
  return date.getDate()
}

// Format time
const formatTime = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleTimeString('en-US', { 
    hour: 'numeric',
    minute: '2-digit',
    hour12: true 
  })
}

// Event handlers for search functionality
const handleCategoryFilter = (event) => {
  const { category, visibleSections } = event.detail
  isVisible.value = visibleSections.includes('events')
}

const handleSearchResults = (event) => {
  const { results, category, query } = event.detail
  searchQuery.value = query
  
  // If this section should be visible and we have results
  if ((category === 'all' || category === 'events') && results?.events) {
    // Filter events that belong to Events category
    const eventResults = results.events.filter(event => {
      // You may want to add category checking here
      return true // For now, show all events
    })
    
    if (eventResults.length > 0) {
      events.value = eventResults
      isVisible.value = true
    } else if (query) {
      // Hide if no results and there was a search
      isVisible.value = false
    }
  }
}

const resetToDefault = () => {
  fetchEvents()
  isVisible.value = true
  searchQuery.value = ''
}

// Navigate to event details
const goToEventDetails = (event) => {
  // Use slug if available, otherwise use ID
  const identifier = event.slug || event.id
  window.location.href = `/listings/${identifier}`
}

// Lifecycle
onMounted(() => {
  fetchEvents()
  
  // Listen for search events
  window.addEventListener('filter-category', handleCategoryFilter)
  window.addEventListener('search-results', handleSearchResults)
})

onUnmounted(() => {
  window.removeEventListener('filter-category', handleCategoryFilter)
  window.removeEventListener('search-results', handleSearchResults)
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