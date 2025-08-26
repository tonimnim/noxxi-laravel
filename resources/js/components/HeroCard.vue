<template>
  <div>
    <!-- Desktop Layout (768px and up) -->
    <div class="hidden md:block relative h-[90vh] min-h-[700px] pt-4 px-8 lg:px-12 xl:px-20 pb-24">
      <div class="bg-[#DCE1E2] rounded-3xl h-full shadow-lg p-8 lg:p-12 relative overflow-visible">
        <!-- Desktop Content -->
        <div class="h-full flex items-center gap-8 lg:gap-12">
          <!-- Left Content -->
          <div class="w-1/2 pr-4">
            <h1 class="text-4xl lg:text-5xl xl:text-6xl 2xl:text-7xl font-bold text-[#223338] mb-4">
              Book Life's Best<br>Moments
            </h1>
            <p class="text-lg lg:text-xl text-[#223338] opacity-80 mb-12">
              Discover amazing events, experiences, and adventures across the world
            </p>
            <div class="flex gap-3">
              <a href="/register" class="bg-[#305F64] text-white px-6 py-3 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity">
                Get Started
              </a>
              <a href="/register/organizer" class="border-2 border-[#223338] text-[#223338] px-6 py-3 rounded-lg text-sm font-medium hover:bg-[#223338] hover:text-white transition-all">
                Sell with us
              </a>
            </div>
          </div>
          
          <!-- Right Images -->
          <div class="w-1/2 h-full relative pt-12 flex items-center justify-center">
            <div class="w-[85%] h-[75%] relative -ml-5">
              <div 
                v-for="(event, index) in trendingEvents" 
                :key="event.id"
                :class="[
                  'absolute inset-0 transition-all duration-1000 ease-in-out rounded-xl overflow-hidden',
                  index === currentImageIndex ? 'opacity-100 scale-100' : 'opacity-0 scale-95'
                ]"
              >
                <img 
                  v-if="event.cover_image_url"
                  :src="event.cover_image_url" 
                  :alt="event.title"
                  class="w-full h-full object-cover"
                >
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-6">
                  <h3 class="text-white text-xl font-semibold">{{ event.title }}</h3>
                  <p class="text-white/80 text-sm mt-1">{{ event.city }} • {{ formatDate(event.event_date) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Search Bar - Desktop Only - OUTSIDE the hero card -->
      <SearchBar />
    </div>
    
    <!-- Mobile Layout (below 768px) -->
    <div class="md:hidden min-h-screen pt-20 px-4 pb-20">
      <div class="bg-[#DCE1E2] rounded-xl shadow-xl p-4">
        <!-- Mobile Images -->
        <div class="h-64 relative rounded-lg overflow-hidden mb-6">
          <div 
            v-for="(event, index) in trendingEvents" 
            :key="event.id"
            :class="[
              'absolute inset-0 transition-all duration-1000 ease-in-out',
              index === currentImageIndex ? 'opacity-100' : 'opacity-0'
            ]"
          >
            <img 
              v-if="event.cover_image_url"
              :src="event.cover_image_url" 
              :alt="event.title"
              class="w-full h-full object-cover"
            >
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-4">
              <h3 class="text-white text-base font-semibold">{{ event.title }}</h3>
              <p class="text-white/80 text-xs mt-1">{{ event.city }} • {{ formatDate(event.event_date) }}</p>
            </div>
          </div>
        </div>
        
        <!-- Mobile Content -->
        <div class="text-center">
          <h1 class="text-3xl font-bold text-[#223338] mb-3">
            Book Life's Best<br>Moments
          </h1>
          <p class="text-base text-[#223338] opacity-80 mb-8 px-2">
            Discover amazing events, experiences, and adventures across the world
          </p>
          <div class="flex gap-3 justify-center mb-6">
            <a href="/register" class="bg-[#305F64] text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity">
              Get Started
            </a>
            <a href="/register/organizer" class="border-2 border-[#223338] text-[#223338] px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-[#223338] hover:text-white transition-all">
              Sell with us
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import SearchBar from './SearchBar.vue'

// State
const currentImageIndex = ref(0)
const trendingEvents = ref([])
let intervalId = null

// Fetch trending events
const fetchTrendingEvents = async () => {
  try {
    const response = await fetch('/api/home/trending')
    const data = await response.json()
    if (data.status === 'success') {
      trendingEvents.value = data.data
      startImageRotation()
    }
  } catch (error) {
    console.error('Error fetching trending events:', error)
  }
}

// Start image rotation
const startImageRotation = () => {
  if (intervalId) clearInterval(intervalId)
  
  intervalId = setInterval(() => {
    if (trendingEvents.value.length > 0) {
      currentImageIndex.value = (currentImageIndex.value + 1) % trendingEvents.value.length
    }
  }, 5000) // Change image every 5 seconds
}

// Format date
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

// Lifecycle
onMounted(() => {
  fetchTrendingEvents()
})

onUnmounted(() => {
  if (intervalId) {
    clearInterval(intervalId)
  }
})
</script>