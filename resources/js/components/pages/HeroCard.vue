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
              <template v-if="!authLoading">
                <a v-if="!isAuthenticated" href="/register" class="bg-[#305F64] text-white px-6 py-3 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity">
                  Get Started
                </a>
                <button @click="handleSellWithUs" class="border-2 border-[#223338] text-[#223338] px-6 py-3 rounded-lg text-sm font-medium hover:bg-[#223338] hover:text-white transition-all">
                  Sell with us
                </button>
              </template>
              <template v-else>
                <button class="border-2 border-[#223338] text-[#223338] px-6 py-3 rounded-lg text-sm font-medium hover:bg-[#223338] hover:text-white transition-all">
                  Sell with us
                </button>
              </template>
            </div>
          </div>
          
          <!-- Right Images -->
          <div class="w-1/2 h-full relative pt-12 flex items-center justify-center">
            <div class="w-[85%] h-[75%] relative -ml-5">
              <!-- Loading placeholder -->
              <div v-if="!imagesLoaded" class="absolute inset-0 rounded-xl bg-gray-200 animate-pulse"></div>
              
              <!-- Images once loaded -->
              <template v-else>
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
              </template>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Search Bar - Desktop Only - OUTSIDE the hero card -->
      <SearchBar />
    </div>
    
    <!-- Mobile Layout (below 768px) -->
    <div class="md:hidden pt-20 px-4 pb-8">
      <div class="bg-[#DCE1E2] rounded-xl shadow-xl p-4">
        <!-- Mobile Images -->
        <div class="h-64 relative rounded-lg overflow-hidden mb-6">
          <!-- Loading placeholder -->
          <div v-if="!imagesLoaded" class="absolute inset-0 bg-gray-200 animate-pulse"></div>
          
          <!-- Images once loaded -->
          <template v-else>
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
          </template>
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
            <template v-if="!authLoading">
              <a v-if="!isAuthenticated" href="/register" class="bg-[#305F64] text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity">
                Get Started
              </a>
              <button @click="handleSellWithUs" class="border-2 border-[#223338] text-[#223338] px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-[#223338] hover:text-white transition-all">
                Sell with us
              </button>
            </template>
            <template v-else>
              <button class="border-2 border-[#223338] text-[#223338] px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-[#223338] hover:text-white transition-all">
                Sell with us
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useAuthStore } from '../../stores/auth'
import SearchBar from '../common/SearchBar.vue'

// Store
const authStore = useAuthStore()

// State
const currentImageIndex = ref(0)
const trendingEvents = ref([])
const imagesLoaded = ref(false)
let intervalId = null

// Computed
const authLoading = computed(() => !authStore.initialized || authStore.loading)
const isAuthenticated = computed(() => authStore.isAuthenticated)
const userType = computed(() => {
  if (!authStore.user) return null
  return authStore.user.organizer ? 'organizer' : 'user'
})

// Preload images to prevent flashing
const preloadImages = async (events) => {
  const promises = events
    .filter(event => event.cover_image_url)
    .map(event => {
      return new Promise((resolve, reject) => {
        const img = new Image()
        img.onload = resolve
        img.onerror = reject
        img.src = event.cover_image_url
      })
    })
  
  try {
    await Promise.all(promises)
    imagesLoaded.value = true
  } catch (error) {
    console.error('Error preloading images:', error)
    imagesLoaded.value = true // Still show even if some images fail
  }
}

// Fetch featured events for hero carousel
const fetchTrendingEvents = async () => {
  try {
    // Check if we have cached data (valid for 5 minutes)
    const cached = sessionStorage.getItem('featured_events')
    const cacheTime = sessionStorage.getItem('featured_events_time')
    const now = Date.now()
    
    if (cached && cacheTime && (now - parseInt(cacheTime)) < 5 * 60 * 1000) {
      // Use cached data
      trendingEvents.value = JSON.parse(cached)
      await preloadImages(trendingEvents.value)
      startImageRotation()
      return
    }
    
    // Request images sized for the hero container (roughly 16:10 aspect ratio)
    const imageParams = new URLSearchParams({
      image_width: 800,
      image_height: 500,
      image_crop: 'fill',
      g: 'auto' // Smart cropping to focus on important parts
    })
    
    // Changed to fetch only featured events
    const response = await fetch(`/api/home/featured?${imageParams}`)
    const data = await response.json()
    if (data.status === 'success') {
      trendingEvents.value = data.data
      
      // Cache the data
      sessionStorage.setItem('featured_events', JSON.stringify(data.data))
      sessionStorage.setItem('featured_events_time', now.toString())
      
      // Preload images before showing
      await preloadImages(trendingEvents.value)
      startImageRotation()
    }
  } catch (error) {
    console.error('Error fetching featured events:', error)
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


// Handle Sell with us button click
const handleSellWithUs = () => {
  if (!isAuthenticated.value) {
    // Not logged in - go to organizer registration
    window.location.href = '/register/organizer'
  } else if (userType.value === 'user') {
    // Logged in as regular user - go to organizer registration
    window.location.href = '/register/organizer'
  } else if (userType.value === 'organizer') {
    // Already an organizer - go to dashboard
    window.location.href = '/organizer/dashboard'
  }
}

// Lifecycle
onMounted(() => {
  fetchTrendingEvents()
  // Auth is initialized before component mounts
})

onUnmounted(() => {
  if (intervalId) {
    clearInterval(intervalId)
  }
})
</script>