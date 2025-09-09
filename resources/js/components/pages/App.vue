<template>
  <div class="min-h-screen bg-white">
    <!-- Homepage Layout -->
    <template v-if="currentPage === 'home'">
      <!-- Header -->
      <AppHeader />
      
      <!-- Main Content -->
      <main class="relative min-h-screen">
        <!-- Hero Section -->
        <HeroCard />
        
        <!-- Featured Events Section -->
        <FeaturedEvents />
        
        <!-- Events Section -->
        <EventsSection />
        
        <!-- Experiences Section -->
        <ExperiencesSection />
        
        <!-- Sports Section -->
        <SportsSection />
        
        <!-- Cinema Section -->
        <CinemaSection />
        
        <!-- App Download Section -->
        <AppDownloadSection />
        
        <!-- Additional content can go here -->
      </main>
      
      <!-- Footer -->
      <AppFooter />
      
      <!-- Mobile Search Button -->
      <MobileSearchButton />
    </template>

    <!-- Category Listing Pages -->
    <template v-else-if="currentPage === 'events'">
      <EventsListingPage />
    </template>
    
    <template v-else-if="currentPage === 'sports'">
      <SportsListingPage />
    </template>
    
    <template v-else-if="currentPage === 'cinema'">
      <CinemaListingPage />
    </template>
    
    <template v-else-if="currentPage === 'experiences'">
      <ExperiencesListingPage />
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import AppHeader from '../layout/AppHeader.vue'
import AppDownloadButtons from '../common/AppDownloadButtons.vue'
import HeroCard from './HeroCard.vue'
import MobileSearchButton from '../common/MobileSearchButton.vue'
import FeaturedEvents from '../events/FeaturedEvents.vue'
import EventsSection from '../events/EventsSection.vue'
import ExperiencesSection from '../events/ExperiencesSection.vue'
import SportsSection from '../events/SportsSection.vue'
import CinemaSection from '../events/CinemaSection.vue'
import AppDownloadSection from '../layout/AppDownloadSection.vue'
import AppFooter from '../layout/AppFooter.vue'
import EventsListingPage from '../events/EventsListingPage.vue'
import SportsListingPage from '../events/SportsListingPage.vue'
import CinemaListingPage from '../events/CinemaListingPage.vue'
import ExperiencesListingPage from '../events/ExperiencesListingPage.vue'

// State
const searchQuery = ref('')
const selectedCategory = ref('events') // Events selected by default
const trendingEvents = ref([])
const currentSlide = ref(0)
let slideInterval = null

// Computed property to determine current page based on URL
const currentPage = computed(() => {
  const path = window.location.pathname
  
  if (path === '/events') return 'events'
  if (path === '/sports') return 'sports'
  if (path === '/cinema') return 'cinema'
  if (path === '/experiences') return 'experiences'
  
  return 'home' // Default to home for all other paths
})

// Methods
const handleSearch = () => {
  if (searchQuery.value.trim()) {
    // Handle search - for now just log it
    console.log('Searching for:', searchQuery.value, 'in category:', selectedCategory.value)
    // In production, this would navigate to search results or call an API
    // window.location.href = `/search?q=${encodeURIComponent(searchQuery.value)}&category=${selectedCategory.value}`
  }
}

const formatPrice = (price) => {
  return parseFloat(price).toLocaleString()
}

const fetchTrendingEvents = async () => {
  try {
    const response = await fetch('/api/home/trending')
    const data = await response.json()
    if (data.status === 'success') {
      trendingEvents.value = data.data
      startSlideshow()
    }
  } catch (error) {
    console.error('Error fetching trending events:', error)
  }
}

const startSlideshow = () => {
  if (slideInterval) clearInterval(slideInterval)
  
  slideInterval = setInterval(() => {
    if (trendingEvents.value.length > 0) {
      currentSlide.value = (currentSlide.value + 1) % trendingEvents.value.length
    }
  }, 5000) // Change slide every 5 seconds
}

// Lifecycle
onMounted(() => {
  // Only fetch trending events on homepage
  if (currentPage.value === 'home') {
    fetchTrendingEvents()
  }
})

onUnmounted(() => {
  if (slideInterval) {
    clearInterval(slideInterval)
  }
})
</script>