<template>
  <section class="py-16 px-4 md:px-8 lg:px-12 xl:px-20">
    <div class="max-w-7xl mx-auto">
      <!-- Section Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h2 class="text-3xl md:text-4xl font-bold text-[#223338]">Featured Events</h2>
          <p class="text-gray-600 mt-2">Discover what's happening around you</p>
        </div>
        <a href="/explore" class="hidden md:inline-flex items-center gap-2 text-[#305F64] font-medium hover:opacity-80 transition-opacity">
          View all
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </a>
      </div>

      <!-- Events Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div 
          v-for="event in featuredEvents" 
          :key="event.id"
          class="group cursor-pointer"
        >
          <div class="bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow duration-300">
            <!-- Event Image -->
            <div class="relative h-48 md:h-56 overflow-hidden">
              <img 
                v-if="event.cover_image_url"
                :src="event.cover_image_url" 
                :alt="event.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              >
              <div v-else class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300"></div>
              
              <!-- Category Badge -->
              <div class="absolute top-4 left-4">
                <span class="px-3 py-1 bg-white/90 backdrop-blur-sm rounded-full text-xs font-medium text-[#223338]">
                  {{ event.category?.name || 'Event' }}
                </span>
              </div>

              <!-- Price Badge -->
              <div class="absolute top-4 right-4">
                <span class="px-3 py-1 bg-[#305F64]/90 backdrop-blur-sm text-white rounded-full text-xs font-medium">
                  {{ formatPrice(event) }}
                </span>
              </div>
            </div>

            <!-- Event Details -->
            <div class="p-4">
              <!-- Date -->
              <div class="text-sm text-gray-500 mb-2">
                {{ formatDate(event.event_date) }}
              </div>

              <!-- Title -->
              <h3 class="font-semibold text-lg text-[#223338] mb-2 line-clamp-2">
                {{ event.title }}
              </h3>

              <!-- Location -->
              <div class="flex items-center gap-2 text-sm text-gray-600 mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span>{{ event.venue_name || event.city }}</span>
              </div>

              <!-- Organizer -->
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">
                  by {{ event.organizer?.business_name || 'Organizer' }}
                </span>
                <button class="text-[#305F64] hover:opacity-80">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Mobile View All Button -->
      <div class="mt-8 text-center md:hidden">
        <a href="/explore" class="inline-flex items-center gap-2 bg-[#305F64] text-white px-6 py-3 rounded-lg font-medium hover:opacity-90 transition-opacity">
          View all events
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </a>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// State
const featuredEvents = ref([])

// Fetch featured events
const fetchFeaturedEvents = async () => {
  try {
    const response = await fetch('/api/home/featured')
    const data = await response.json()
    if (data.status === 'success') {
      featuredEvents.value = data.data
    }
  } catch (error) {
    console.error('Error fetching featured events:', error)
  }
}

// Format price
const formatPrice = (event) => {
  if (!event.min_price || event.min_price === 0) {
    return 'Free'
  }
  const currency = event.currency || 'KES'
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

// Lifecycle
onMounted(() => {
  fetchFeaturedEvents()
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>