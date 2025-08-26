<template>
  <section v-show="isVisible" class="py-16 px-4 md:px-8 lg:px-12 xl:px-20">
    <div class="max-w-7xl mx-auto">
      <!-- Section Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h2 class="text-3xl md:text-4xl font-bold text-[#223338]">Cinema</h2>
          <p class="text-gray-600 mt-2">Movies and screenings near you</p>
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

              <!-- Showtime & Cinema -->
              <div class="space-y-2 mb-4">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  <span>{{ formatShowtimes(movie) }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4"></path>
                  </svg>
                  <span class="line-clamp-1">{{ movie.venue_name || movie.cinema || movie.city }}</span>
                </div>
              </div>

              <!-- Cinema Chain & Action -->
              <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-gradient-to-br from-red-500 to-purple-600 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                    </svg>
                  </div>
                  <span class="text-sm text-gray-600">{{ movie.organizer?.business_name || 'Cinema' }}</span>
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

// Fetch movies
const fetchMovies = async (loadingMore = false) => {
  if (loading.value) return
  loading.value = true

  try {
    const response = await fetch(`/api/cinema?page=${page.value}&per_page=12`)
    const data = await response.json()
    
    if (data.status === 'success') {
      if (loadingMore) {
        movies.value = [...movies.value, ...data.data]
      } else {
        movies.value = data.data
      }
      
      // Check if there are more pages
      hasMore.value = data.meta && data.meta.current_page < data.meta.last_page
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

// Format showtimes
const formatShowtimes = (movie) => {
  if (movie.showtimes && movie.showtimes.length > 0) {
    // If movie has multiple showtimes
    return movie.showtimes.slice(0, 2).join(', ') + (movie.showtimes.length > 2 ? '...' : '')
  } else if (movie.event_date) {
    // Single showtime
    const date = new Date(movie.event_date)
    return date.toLocaleTimeString('en-US', { 
      hour: 'numeric',
      minute: '2-digit',
      hour12: true 
    })
  }
  return 'Multiple showtimes'
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
    // Filter cinema/movies from results
    const cinemaResults = results.events.filter(event => {
      // Check if category is Cinema-related
      return true // For now, show all
    })
    
    if (cinemaResults.length > 0) {
      movies.value = cinemaResults
      isVisible.value = true
    } else if (query) {
      // Hide if no results and there was a search
      isVisible.value = false
    }
  }
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