<template>
  <section v-show="isVisible && experiences.length > 0" class="py-16 px-4 md:px-8 lg:px-12 xl:px-20">
    <div class="max-w-7xl mx-auto">
      <!-- Section Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h2 class="text-3xl md:text-4xl font-bold text-[#223338]">Experiences</h2>
        </div>
        <a href="/experiences" class="hidden md:inline-flex items-center gap-2 text-[#305F64] font-medium hover:opacity-80 transition-opacity">
          View all experiences
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </a>
      </div>

      <!-- Experiences Grid - Responsive 2/3/4 Columns -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-5 lg:gap-6">
        <div 
          v-for="experience in experiences" 
          :key="experience.id"
          class="group cursor-pointer"
          @click="goToEventDetails(experience)"
        >
          <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-xl transition-all duration-300">
            <!-- Experience Image -->
            <div class="relative h-40 sm:h-48 lg:h-44 xl:h-48 overflow-hidden">
              <img 
                v-if="experience.cover_image_url"
                :src="experience.cover_image_url" 
                :alt="experience.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              >
              <div v-else class="w-full h-full bg-gradient-to-br from-blue-400 to-purple-500"></div>

              <!-- Price Badge -->
              <div class="absolute top-3 right-3">
                <span class="px-2 lg:px-3 py-0.5 lg:py-1 bg-[#305F64]/90 backdrop-blur-sm text-white rounded-full text-xs lg:text-sm font-medium">
                  {{ formatPrice(experience) }}
                </span>
              </div>

              <!-- Category -->
              <div class="absolute bottom-3 left-3">
                <span class="px-2 lg:px-3 py-0.5 lg:py-1 bg-white/90 backdrop-blur-sm rounded-full text-[10px] lg:text-xs font-medium text-[#223338]">
                  {{ experience.category?.name || 'Experience' }}
                </span>
              </div>
            </div>

            <!-- Experience Details -->
            <div class="p-4 lg:p-4 xl:p-5">
              <!-- Title -->
              <h3 class="font-bold text-base lg:text-lg xl:text-xl text-[#223338] line-clamp-2 group-hover:text-[#305F64] transition-colors">
                {{ experience.title }}
              </h3>

              <!-- Date -->
              <div class="text-sm text-gray-600 mb-1">
                {{ formatDate(experience.event_date) }}
              </div>

              <!-- Location -->
              <div class="flex items-center gap-2 text-sm text-gray-600 mb-3">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="line-clamp-1">{{ experience.venue_address }}</span>
              </div>

              <!-- Description Extract -->
              <p class="text-sm text-gray-700 line-clamp-2">
                {{ experience.description ? (experience.description.length > 100 ? experience.description.substring(0, 100) + '...' : experience.description) : '' }}
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
          Load more experiences
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
const experiences = ref([])
const page = ref(1)
const hasMore = ref(true)
const loading = ref(false)
const isVisible = ref(true)
const searchQuery = ref('')

// Fetch experiences
const fetchExperiences = async (loadingMore = false) => {
  if (loading.value) return
  loading.value = true

  try {
    const response = await fetch(`/api/experiences?page=${page.value}&per_page=12`)
    const data = await response.json()
    
    if (data.status === 'success') {
      // Extract events array from response
      const experiencesData = data.data?.events || []
      if (loadingMore) {
        experiences.value = [...experiences.value, ...experiencesData]
      } else {
        experiences.value = experiencesData
      }
      
      // Check if there are more pages
      const meta = data.data?.meta || {}
      hasMore.value = meta.current_page < meta.last_page
    }
  } catch (error) {
    console.error('Error fetching experiences:', error)
  } finally {
    loading.value = false
  }
}

// Load more experiences
const loadMore = () => {
  page.value++
  fetchExperiences(true)
}

// Format price
const formatPrice = (experience) => {
  if (!experience.min_price || experience.min_price === 0) {
    return 'Free'
  }
  const currency = experience.currency || 'KES'
  const price = parseFloat(experience.min_price).toLocaleString()
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
  isVisible.value = visibleSections.includes('experiences')
}

const handleSearchResults = (event) => {
  const { results, category, query } = event.detail
  searchQuery.value = query
  
  // If this section should be visible and we have results
  if ((category === 'all' || category === 'experiences') && results?.events) {
    // Filter experiences from results
    const experienceResults = results.events.filter(event => {
      // Check if category is Experiences-related
      return true // For now, show all
    })
    
    if (experienceResults.length > 0) {
      experiences.value = experienceResults
      isVisible.value = true
    } else if (query) {
      // Hide if no results and there was a search
      isVisible.value = false
    }
  }
}

// Navigate to event details
const goToEventDetails = (experience) => {
  const identifier = experience.slug || experience.id
  window.location.href = `/listings/${identifier}`
}

// Lifecycle
onMounted(() => {
  fetchExperiences()
  
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