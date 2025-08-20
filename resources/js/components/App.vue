<template>
  <div class="min-h-screen bg-white">
    <!-- Header -->
    <AppHeader />
    
    <!-- Main Content -->
    <main class="relative min-h-screen">
      <!-- Hero Section -->
      <HeroCard />
      
      <!-- Featured Events Section -->
      <FeaturedEvents />
      
      <!-- Popular Categories Section -->
      <PopularCategories />
      
      <!-- Additional content can go here -->
    </main>
    
    <!-- Mobile Search Button -->
    <MobileSearchButton />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import AppHeader from './AppHeader.vue'
import AppDownloadButtons from './AppDownloadButtons.vue'
import HeroCard from './HeroCard.vue'
import MobileSearchButton from './MobileSearchButton.vue'
import FeaturedEvents from './FeaturedEvents.vue'
import PopularCategories from './PopularCategories.vue'

// State
const searchQuery = ref('')
const selectedCategory = ref('events') // Events selected by default
const trendingEvents = ref([])
const currentSlide = ref(0)
let slideInterval = null

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
  fetchTrendingEvents()
})

onUnmounted(() => {
  if (slideInterval) {
    clearInterval(slideInterval)
  }
})
</script>