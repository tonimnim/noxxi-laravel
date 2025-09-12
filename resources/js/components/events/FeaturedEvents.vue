<template>
  <section id="featured-events" v-show="isVisible" class="pt-0 pb-8 md:py-16 px-4 md:px-8 lg:px-12 xl:px-20">
    <div class="max-w-7xl mx-auto">
      <!-- Section Header -->
      <div class="mb-8">
        <h2 class="text-3xl md:text-4xl font-bold text-[#223338]">{{ sectionTitle }}</h2>
      </div>

      <!-- Events Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <a 
          v-for="event in featuredEvents" 
          :key="event.id"
          :href="`/listings/${event.slug || event.id}`"
          class="group cursor-pointer block"
        >
          <div class="bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-shadow duration-300 h-full flex flex-col">
            <!-- Event Image -->
            <div 
              class="relative aspect-[4/3] md:aspect-[3/2] bg-cover bg-center rounded-t-xl"
              :style="event.cover_image_url ? `background-image: url('${event.cover_image_url}')` : ''"
              v-bind:class="!event.cover_image_url ? 'bg-gradient-to-br from-gray-200 to-gray-300' : ''"
            >
              
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
            <div class="p-5 flex-1 flex flex-col">
              <!-- Title -->
              <h3 class="font-semibold text-lg lg:text-xl text-[#223338] line-clamp-2 min-h-[3.5rem]">
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
                <span>{{ event.venue_address }}</span>
              </div>

              <!-- Description Extract -->
              <p class="text-sm text-gray-700 line-clamp-2 min-h-[2.5rem]">
                {{ event.description ? (event.description.length > 100 ? event.description.substring(0, 100) + '...' : event.description) : '' }}
              </p>
            </div>
          </div>
        </a>
      </div>

    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

// State
const featuredEvents = ref([])
const sectionTitle = ref('Featured at Noxxi')
const isVisible = ref(true)
const currentCategory = ref('all')

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

// Event handlers for search functionality
const handleCategoryFilter = (event) => {
  const { category, visibleSections } = event.detail
  currentCategory.value = category
  
  // Update title based on category
  switch(category) {
    case 'all':
      sectionTitle.value = 'Featured at Noxxi'
      isVisible.value = true
      break
    case 'events':
      sectionTitle.value = 'Featured Events'
      isVisible.value = visibleSections.includes('featured-events')
      break
    case 'sports':
      sectionTitle.value = 'Featured Sports'
      isVisible.value = visibleSections.includes('featured-events')
      break
    case 'cinema':
      sectionTitle.value = 'Featured Cinema'
      isVisible.value = visibleSections.includes('featured-events')
      break
    case 'experiences':
      sectionTitle.value = 'Featured Experiences'
      isVisible.value = visibleSections.includes('featured-events')
      break
    default:
      sectionTitle.value = 'Featured at Noxxi'
      isVisible.value = true
  }
  
  // Fetch category-specific featured items
  fetchFeaturedEvents(category)
}

const handleSearchResults = (event) => {
  const { results, category, query } = event.detail
  
  // If there's a search query, update featured items with top results
  if (query && results?.events && results.events.length > 0) {
    featuredEvents.value = results.events.slice(0, 8) // Show top 8 results
  } else if (query && (!results?.events || results.events.length === 0)) {
    // No results found
    featuredEvents.value = []
  }
}

const handleSearchCleared = () => {
  // Reset to default featured content
  fetchFeaturedEvents(currentCategory.value)
}

// Cache configuration
const CACHE_KEY_PREFIX = 'featured_events_'
const CACHE_TIME_PREFIX = 'featured_events_time_'
const CACHE_DURATION = 10 * 60 * 1000 // 10 minutes cache

// Get cached data if valid
const getCachedData = (category) => {
  const cacheKey = CACHE_KEY_PREFIX + category
  const cacheTimeKey = CACHE_TIME_PREFIX + category
  const cached = sessionStorage.getItem(cacheKey)
  const cacheTime = sessionStorage.getItem(cacheTimeKey)
  const now = Date.now()
  
  if (cached && cacheTime && (now - parseInt(cacheTime)) < CACHE_DURATION) {
    const data = JSON.parse(cached)
    return data
  }
  return null
}

// Save data to cache
const saveToCache = (category, data) => {
  const cacheKey = CACHE_KEY_PREFIX + category
  const cacheTimeKey = CACHE_TIME_PREFIX + category
  
  sessionStorage.setItem(cacheKey, JSON.stringify(data))
  sessionStorage.setItem(cacheTimeKey, Date.now().toString())
}

// Modified fetch to support category filtering
const fetchFeaturedEvents = async (category = 'all') => {
  try {
    // Check cache first
    const cachedData = getCachedData(category)
    if (cachedData) {
      featuredEvents.value = cachedData
      return
    }
    
    // Request images sized for featured cards (3:2 aspect ratio)
    const imageParams = new URLSearchParams({
      image_width: 600,
      image_height: 400,
      image_crop: 'fill',
      g: 'auto' // Smart cropping to focus on important parts
    })
    
    const endpoint = category === 'all' 
      ? `/api/home/featured?${imageParams}` 
      : `/api/home/featured?category=${category}&${imageParams}`
    
    const response = await fetch(endpoint)
    const data = await response.json()
    if (data.status === 'success') {
      featuredEvents.value = data.data
      // Save to cache
      saveToCache(category, data.data)
    }
  } catch (error) {
    console.error('Error fetching featured events:', error)
  }
}

// Lifecycle
onMounted(() => {
  fetchFeaturedEvents()
  
  // Listen for category filter events
  window.addEventListener('filter-category', handleCategoryFilter)
  window.addEventListener('search-results', handleSearchResults)
  window.addEventListener('search-cleared', handleSearchCleared)
})

onUnmounted(() => {
  window.removeEventListener('filter-category', handleCategoryFilter)
  window.removeEventListener('search-results', handleSearchResults)
  window.removeEventListener('search-cleared', handleSearchCleared)
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