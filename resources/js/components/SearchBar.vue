<template>
  <div class="hidden md:block absolute left-[72px] lg:left-[88px] xl:left-[120px] bottom-0 z-30 w-[70%] lg:w-[60%] max-w-3xl">
    <!-- Search Container -->
    <div class="bg-white rounded-2xl shadow-2xl px-8 py-5 border border-gray-100">
      <!-- Desktop Layout - Inline -->
      <div class="flex items-center gap-3">
        <!-- Search Input -->
        <div class="flex-1 relative">
          <input
            type="text"
            v-model="searchQuery"
            @keyup.enter="handleSearch"
            placeholder="Search events..."
            class="w-full px-3 md:px-4 py-2.5 md:py-3 pl-10 md:pl-11 pr-3 md:pr-4 text-sm md:text-base text-gray-700 bg-gray-50 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-200 transition-all"
          >
          <svg class="absolute left-3 md:left-4 top-1/2 transform -translate-y-1/2 w-4 md:w-5 h-4 md:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>

        <!-- Location Input with Dropdown -->
        <div class="relative" ref="locationDropdown">
          <input
            type="text"
            v-model="location"
            @focus="showLocationDropdown = true"
            @input="filterLocations"
            placeholder="Location"
            class="w-32 lg:w-40 px-3 md:px-4 py-2.5 md:py-3 pl-9 md:pl-10 text-sm md:text-base text-gray-700 bg-gray-50 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-200 transition-all"
          >
          <svg class="absolute left-2.5 md:left-3 top-1/2 transform -translate-y-1/2 w-4 md:w-5 h-4 md:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          
          <!-- Location Dropdown -->
          <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
          >
            <div 
              v-if="showLocationDropdown"
              class="absolute top-full mt-1 w-64 bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden z-30"
            >
              <!-- Use My Location Option -->
              <button
                @click="useMyLocation"
                class="w-full px-4 py-3 text-left hover:bg-gray-50 flex items-center gap-3 border-b border-gray-100"
              >
                <svg class="w-5 h-5 text-[#305F64]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="text-[#305F64] font-medium">Use my location</span>
              </button>
              
              <!-- Popular Locations -->
              <div class="max-h-48 overflow-y-auto">
                <button
                  v-for="loc in filteredLocations"
                  :key="loc"
                  @click="selectLocation(loc)"
                  class="w-full px-4 py-2.5 text-left hover:bg-gray-50 text-gray-700"
                >
                  {{ loc }}
                </button>
              </div>
            </div>
          </transition>
        </div>

        <!-- Date Picker -->
        <div class="relative">
          <input
            type="date"
            v-model="selectedDate"
            class="w-32 lg:w-40 px-3 md:px-4 py-2.5 md:py-3 pl-9 md:pl-10 text-sm md:text-base text-gray-700 bg-gray-50 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-200 transition-all appearance-none"
          >
          <svg class="absolute left-2.5 md:left-3 top-1/2 transform -translate-y-1/2 w-4 md:w-5 h-4 md:h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
        </div>

        <!-- Search Button -->
        <button
          @click="handleSearch"
          class="px-4 md:px-6 lg:px-8 py-2.5 md:py-3 bg-[#305F64] text-white rounded-lg text-sm md:text-base font-medium hover:opacity-90 transition-opacity"
        >
          Search
        </button>
      </div>

      <!-- Category Pills - Desktop Only -->
      <div class="hidden md:flex items-center gap-2 mt-3 pt-3 border-t border-gray-100">
        <span class="text-sm text-gray-500">Popular:</span>
        <button
          v-for="category in categories"
          :key="category.value"
          @click="selectCategory(category.value)"
          :class="[
            'px-4 py-2 rounded-full text-xs font-medium transition-all',
            selectedCategory === category.value
              ? 'bg-[#305F64] text-white'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ category.label }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

// State
const searchQuery = ref('')
const location = ref('')
const selectedDate = ref('')
const selectedCategory = ref('all')
const showLocationDropdown = ref(false)
const locationDropdown = ref(null)
const mobileLocationDropdown = ref(null)
const filteredLocations = ref([])
const isMobile = ref(false)

const categories = [
  { label: 'All', value: 'all' },
  { label: 'Events', value: 'events' },
  { label: 'Sports', value: 'sports' },
  { label: 'Cinema', value: 'cinema' },
  { label: 'Experiences', value: 'experiences' }
]

const popularLocations = [
  'Nairobi',
  'Lagos',
  'Cape Town',
  'Cairo',
  'Johannesburg',
  'Accra',
  'Dar es Salaam',
  'Kampala',
  'Addis Ababa',
  'Casablanca'
]

// Initialize with popular locations
filteredLocations.value = popularLocations

// Methods
const handleSearch = () => {
  if (searchQuery.value.trim() || location.value.trim() || selectedDate.value) {
    console.log('Searching:', {
      query: searchQuery.value,
      location: location.value,
      date: selectedDate.value,
      category: selectedCategory.value
    })
    // In production, navigate to search results
  }
}

const selectCategory = (category) => {
  selectedCategory.value = category
  // Could trigger a search or filter here
}

const filterLocations = () => {
  const query = location.value.toLowerCase()
  if (query) {
    filteredLocations.value = popularLocations.filter(loc => 
      loc.toLowerCase().includes(query)
    )
  } else {
    filteredLocations.value = popularLocations
  }
}

const selectLocation = (loc) => {
  location.value = loc
  showLocationDropdown.value = false
}

const useMyLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        // In production, reverse geocode to get city name
        location.value = 'Current Location'
        showLocationDropdown.value = false
        console.log('Location:', position.coords.latitude, position.coords.longitude)
      },
      (error) => {
        console.error('Error getting location:', error)
        alert('Unable to get your location. Please enter manually.')
      }
    )
  } else {
    alert('Geolocation is not supported by your browser')
  }
}

const handleClickOutside = (event) => {
  if (locationDropdown.value && !locationDropdown.value.contains(event.target)) {
    showLocationDropdown.value = false
  }
  if (mobileLocationDropdown.value && !mobileLocationDropdown.value.contains(event.target)) {
    showLocationDropdown.value = false
  }
}

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('resize', checkMobile)
})
</script>

<style scoped>
/* Hide native date picker icon */
input[type="date"]::-webkit-calendar-picker-indicator {
  display: none;
  -webkit-appearance: none;
}
</style>