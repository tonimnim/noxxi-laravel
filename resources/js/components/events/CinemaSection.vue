<template>
  <section v-show="isVisible && movies.length > 0" class="py-16 px-4 md:px-8 lg:px-12 xl:px-20">
    <div class="max-w-7xl mx-auto">
      <!-- Section Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h2 class="text-3xl md:text-4xl font-bold text-[#223338]">Cinema</h2>
        </div>
        <a href="/cinema" class="hidden md:inline-flex items-center gap-2 text-[#305F64] font-medium hover:opacity-80 transition-opacity">
          View all movies
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </a>
      </div>

      <!-- Cinema Grid - Responsive 2/3/4 Columns -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-5 lg:gap-6">
        <div 
          v-for="movie in movies" 
          :key="movie.id"
          class="group cursor-pointer"
          @click="goToEventDetails(movie)"
        >
          <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-xl transition-all duration-300">
            <!-- Movie Poster -->
            <div class="relative h-40 sm:h-48 lg:h-44 xl:h-48 overflow-hidden">
              <img 
                v-if="movie.cover_image_url"
                :src="movie.cover_image_url" 
                :alt="movie.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              >
              <div v-else class="w-full h-full bg-gradient-to-br from-red-500 to-purple-600"></div>
              
              <!-- Rating Badge (if available) -->
              <div v-if="movie.rating" class="absolute top-3 left-3 bg-black/80 text-yellow-400 rounded px-2 py-1 text-xs font-bold flex items-center gap-1">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                {{ movie.rating }}
              </div>

              <!-- Price Badge -->
              <div class="absolute top-3 right-3">
                <span class="px-2 lg:px-3 py-0.5 lg:py-1 bg-[#305F64]/90 backdrop-blur-sm text-white rounded-full text-xs lg:text-sm font-medium">
                  {{ formatPrice(movie) }}
                </span>
              </div>

              <!-- Genre -->
              <div class="absolute bottom-3 left-3">
                <span class="px-2 lg:px-3 py-0.5 lg:py-1 bg-white/90 backdrop-blur-sm rounded-full text-[10px] lg:text-xs font-medium text-[#223338]">
                  {{ movie.genre || movie.category?.name || 'Movie' }}
                </span>
              </div>

              <!-- Duration Badge -->
              <div v-if="movie.duration" class="absolute bottom-3 right-3">
                <span class="px-2 lg:px-3 py-0.5 lg:py-1 bg-black/70 text-white rounded-full text-[10px] lg:text-xs font-medium">
                  {{ movie.duration }} min
                </span>
              </div>
            </div>

            <!-- Movie Details -->
            <div class="p-4 lg:p-4 xl:p-5">
              <!-- Title -->
              <h3 class="font-bold text-base lg:text-lg xl:text-xl text-[#223338] mb-2 line-clamp-2 group-hover:text-[#305F64] transition-colors">
                {{ movie.title }}
              </h3>

              <!-- Date -->
              <div class="text-sm text-gray-600 mb-1">
                {{ formatDate(movie.event_date) }}
              </div>

              <!-- Location -->
              <div class="flex items-center gap-2 text-sm text-gray-600 mb-3">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="line-clamp-1">{{ movie.venue_address }}</span>
              </div>

              <!-- Description Extract -->
              <p class="text-sm text-gray-700 line-clamp-2">
                {{ movie.description ? (movie.description.length > 100 ? movie.description.substring(0, 100) + '...' : movie.description) : '' }}
              </p>
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
          Load more movies
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
const movies = ref([])
const page = ref(1)
const hasMore = ref(true)
const loading = ref(false)
const isVisible = ref(true)
const searchQuery = ref('')

// Cache configuration
const CACHE_KEY = 'cinema_section_data'
const CACHE_TIME_KEY = 'cinema_section_time'
const CACHE_DURATION = 10 * 60 * 1000 // 10 minutes cache

// Get cached data if valid
const getCachedData = () => {
  const cached = sessionStorage.getItem(CACHE_KEY)
  const cacheTime = sessionStorage.getItem(CACHE_TIME_KEY)
  const now = Date.now()
  
  if (cached && cacheTime && (now - parseInt(cacheTime)) < CACHE_DURATION) {
    const data = JSON.parse(cached)
    return data
  }
  return null
}

// Save data to cache
const saveToCache = (pageNum, data, meta) => {
  const existing = getCachedData() || { pages: {}, meta: {} }
  existing.pages[pageNum] = data
  existing.meta = meta
  existing.lastPage = pageNum
  
  sessionStorage.setItem(CACHE_KEY, JSON.stringify(existing))
  sessionStorage.setItem(CACHE_TIME_KEY, Date.now().toString())
}

// Fetch movies
const fetchMovies = async (loadingMore = false) => {
  if (loading.value) return
  loading.value = true

  try {
    // Check cache first
    const cachedData = getCachedData()
    if (cachedData && cachedData.pages[page.value]) {
      const moviesData = cachedData.pages[page.value]
      const meta = cachedData.meta
      
      if (loadingMore) {
        movies.value = [...movies.value, ...moviesData]
      } else {
        // Restore all cached pages up to current page
        let allMovies = []
        for (let i = 1; i <= page.value; i++) {
          if (cachedData.pages[i]) {
            allMovies = [...allMovies, ...cachedData.pages[i]]
          }
        }
        movies.value = allMovies
      }
      
      hasMore.value = meta.current_page < meta.last_page
      loading.value = false
      return
    }
    
    // Not in cache, fetch from API
    const response = await fetch(`/api/cinema?page=${page.value}&per_page=12`)
    const data = await response.json()
    
    if (data.status === 'success') {
      // Extract events array from response
      const moviesData = data.data?.events || []
      const meta = data.data?.meta || {}
      
      // Save to cache
      saveToCache(page.value, moviesData, meta)
      
      if (loadingMore) {
        movies.value = [...movies.value, ...moviesData]
      } else {
        movies.value = moviesData
      }
      
      // Check if there are more pages
      hasMore.value = meta.current_page < meta.last_page
    }
  } catch (error) {
    console.error('Error fetching movies:', error)
  } finally {
    loading.value = false
  }
}

// Load more movies
const loadMore = () => {
  page.value++
  fetchMovies(true)
}

// Format price
const formatPrice = (movie) => {
  if (!movie.min_price || movie.min_price === 0) {
    return 'Free'
  }
  const currency = movie.currency || 'KES'
  const price = parseFloat(movie.min_price).toLocaleString()
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
  isVisible.value = visibleSections.includes('cinema')
}

const handleSearchResults = (event) => {
  const { results, category, query } = event.detail
  searchQuery.value = query
  
  // If this section should be visible and we have results
  if ((category === 'all' || category === 'cinema') && results?.events) {
    // Filter events that belong to Cinema category
    const cinemaResults = results.events.filter(event => {
      // Check if the event's category is Cinema
      const categorySlug = event.category?.slug || ''
      const parentSlug = event.category?.parent?.slug || ''
      return categorySlug === 'cinema' || parentSlug === 'cinema'
    })
    
    if (cinemaResults.length > 0) {
      movies.value = cinemaResults
      isVisible.value = true
      hasMore.value = false // Disable load more for search results
    } else if (query) {
      // No results found for search
      movies.value = []
      isVisible.value = true // Still show section but with no results
    }
  }
}

const handleSearchCleared = () => {
  // Reset to default content
  searchQuery.value = ''
  page.value = 1
  fetchMovies()
  isVisible.value = true
}

// Navigate to event details
const goToEventDetails = (movie) => {
  const identifier = movie.slug || movie.id
  window.location.href = `/listings/${identifier}`
}

// Lifecycle
onMounted(() => {
  fetchMovies()
  
  // Listen for search events
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