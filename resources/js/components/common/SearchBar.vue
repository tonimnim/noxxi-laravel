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
            @input="debouncedSearch"
            @keyup.enter="handleSearch"
            placeholder="Search events..."
            class="w-full px-3 md:px-4 py-2.5 md:py-3 pl-10 md:pl-11 pr-3 md:pr-4 text-sm md:text-base text-gray-700 bg-gray-50 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#305F64]/20 transition-all"
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
            @focus="handleLocationFocus"
            @input="filterLocations"
            placeholder="Location"
            class="w-32 lg:w-40 px-3 md:px-4 py-2.5 md:py-3 pl-9 md:pl-10 text-sm md:text-base text-gray-700 bg-gray-50 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#305F64]/20 transition-all"
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
            @change="handleDateChange"
            :min="minDate"
            class="w-32 lg:w-40 px-3 md:px-4 py-2.5 md:py-3 pl-9 md:pl-10 text-sm md:text-base text-gray-700 bg-gray-50 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#305F64]/20 transition-all cursor-pointer"
          >
          <svg class="absolute left-2.5 md:left-3 top-1/2 transform -translate-y-1/2 w-4 md:w-5 h-4 md:h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
        </div>

        <!-- Search Button -->
        <button
          @click="handleSearch"
          :disabled="isSearching"
          class="px-4 md:px-6 lg:px-8 py-2.5 md:py-3 bg-[#305F64] text-white rounded-lg text-sm md:text-base font-medium hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="!isSearching">Search</span>
          <span v-else class="flex items-center gap-2">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Searching...
          </span>
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
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'

// Emit events to parent
const emit = defineEmits(['search', 'category-change'])

// State
const searchQuery = ref('')
const location = ref('')
const selectedDate = ref('')
const selectedCategory = ref('all')
const showLocationDropdown = ref(false)
const locationDropdown = ref(null)
const filteredLocations = ref([])
const allCities = ref([])
const isSearching = ref(false)
const citiesLoaded = ref(false)
let searchTimeout = null

// Computed
const minDate = computed(() => {
  const today = new Date()
  const year = today.getFullYear()
  const month = String(today.getMonth() + 1).padStart(2, '0')
  const day = String(today.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
})

const categories = [
  { label: 'All', value: 'all' },
  { label: 'Events', value: 'events' },
  { label: 'Sports', value: 'sports' },
  { label: 'Cinema', value: 'cinema' },
  { label: 'Experiences', value: 'experiences' }
]


// Methods
const loadCities = async () => {
  if (citiesLoaded.value) return
  
  try {
    const response = await fetch('/api/cities/popular')
    const data = await response.json()
    
    if (data.status === 'success' && data.data) {
      allCities.value = data.data.map(city => city.display_name)
      filteredLocations.value = allCities.value
      citiesLoaded.value = true
    }
  } catch (error) {
    console.error('Error loading cities:', error)
    // Fallback to some default cities if API fails
    allCities.value = [
      'Nairobi, Kenya',
      'Lagos, Nigeria', 
      'Cape Town, South Africa',
      'Cairo, Egypt',
      'Johannesburg, South Africa',
      'Accra, Ghana',
      'Dar es Salaam, Tanzania',
      'Kampala, Uganda',
      'Addis Ababa, Ethiopia',
      'Casablanca, Morocco'
    ]
    filteredLocations.value = allCities.value
  }
}

const handleSearch = async () => {
  if (!searchQuery.value.trim() && !location.value.trim() && !selectedDate.value && selectedCategory.value === 'all') {
    return // Don't search if all fields are empty
  }

  isSearching.value = true
  
  const searchParams = {
    query: searchQuery.value.trim(),
    location: location.value.trim(),
    date: selectedDate.value,
    category: selectedCategory.value
  }
  
  emit('search', searchParams)
  
  // Simulate API call delay
  setTimeout(() => {
    isSearching.value = false
    performSearch(searchParams)
  }, 500)
}

const performSearch = async (params) => {
  try {
    // Build query string
    const queryParams = new URLSearchParams()
    if (params.query) queryParams.append('q', params.query)
    if (params.location) queryParams.append('location', params.location)
    if (params.date) queryParams.append('date', params.date)
    if (params.category && params.category !== 'all') queryParams.append('category', params.category)
    
    // Fetch search results
    const response = await fetch(`/api/search?${queryParams.toString()}`)
    const data = await response.json()
    
    if (data.status === 'success') {
      // The API returns { events: [...] } in data.data
      // Update the sections based on search results
      updateSectionsWithResults(data.data)
    } else {
      // If no results or error, clear the results
      updateSectionsWithResults({ events: [] })
    }
  } catch (error) {
    console.error('Search error:', error)
    // On error, clear the results
    updateSectionsWithResults({ events: [] })
  }
}

const updateSectionsWithResults = (results) => {
  // Ensure results have the expected structure
  const structuredResults = {
    events: results?.events || results || []
  }
  
  // Emit event to parent to update sections
  window.dispatchEvent(new CustomEvent('search-results', { 
    detail: { 
      results: structuredResults,
      category: selectedCategory.value,
      query: searchQuery.value,
      location: location.value,
      date: selectedDate.value
    }
  }))
}

// Debounced search for auto-search as user types
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    if (searchQuery.value.length >= 3) {
      handleSearch()
    } else if (searchQuery.value.length === 0 && !location.value && !selectedDate.value) {
      // If search is cleared and no filters, reset to default
      clearSearch()
    }
  }, 500) // 500ms debounce
}

const clearSearch = () => {
  // Dispatch event to clear search and show default content
  window.dispatchEvent(new CustomEvent('search-cleared', { 
    detail: { 
      category: selectedCategory.value
    }
  }))
}

const selectCategory = (category) => {
  selectedCategory.value = category
  emit('category-change', category)
  
  // Filter sections based on category
  filterSectionsByCategory(category)
  
  // Trigger search if there's a query
  if (searchQuery.value || location.value || selectedDate.value) {
    handleSearch()
  }
}

const filterSectionsByCategory = (category) => {
  // Show/hide sections based on selected category
  const sections = {
    'all': ['featured-events', 'events', 'experiences', 'sports', 'cinema'],
    'events': ['featured-events', 'events'],
    'sports': ['sports'],
    'cinema': ['cinema'],
    'experiences': ['experiences']
  }
  
  // Emit event to show/hide sections
  window.dispatchEvent(new CustomEvent('filter-category', { 
    detail: { 
      category,
      visibleSections: sections[category] || []
    }
  }))
}

const handleLocationFocus = () => {
  showLocationDropdown.value = true
  // Load cities if not already loaded
  if (!citiesLoaded.value) {
    loadCities()
  }
  // Initialize filtered locations if empty
  if (filteredLocations.value.length === 0 && allCities.value.length > 0) {
    filteredLocations.value = allCities.value.slice(0, 20)
  }
}

const filterLocations = () => {
  const query = location.value.toLowerCase()
  if (query) {
    filteredLocations.value = allCities.value.filter(loc => 
      loc.toLowerCase().includes(query)
    )
    // Limit to 20 results for performance
    if (filteredLocations.value.length > 20) {
      filteredLocations.value = filteredLocations.value.slice(0, 20)
    }
  } else {
    // Show first 20 cities when no search query
    filteredLocations.value = allCities.value.slice(0, 20)
  }
}

const selectLocation = (loc) => {
  location.value = loc
  showLocationDropdown.value = false
  
  // Trigger search when location is selected
  if (searchQuery.value || selectedDate.value) {
    handleSearch()
  }
}

const useMyLocation = async () => {
  // First try IP-based geolocation as it doesn't require permissions
  try {
    const response = await fetch('/api/location/detect')
    const data = await response.json()
    
    if (data.status === 'success' && data.data) {
      const locationData = data.data
      location.value = locationData.country
      showLocationDropdown.value = false
      
      // Trigger search with detected location
      if (searchQuery.value || selectedDate.value) {
        handleSearch()
      }
      
      return // Success - no need to try browser geolocation
    }
  } catch (error) {
    // Silent fail
  }
  
  // Fallback to browser geolocation if IP detection fails
  // Check if the page is served over HTTPS or localhost
  const isSecureContext = window.isSecureContext
  
  if (!isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
    alert('Unable to detect your location automatically. Please enter your location manually.')
    return
  }
  
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      async (position) => {
        try {
          // Use reverse geocoding to get country name
          const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.coords.latitude}&lon=${position.coords.longitude}&zoom=3&addressdetails=1`
          )
          const data = await response.json()
          
          // Get country name from the response
          const country = data.address?.country || 'Current Location'
          location.value = country
        } catch (error) {
          location.value = 'Current Location'
        }
        showLocationDropdown.value = false
        
        // Trigger search with current location
        if (searchQuery.value || selectedDate.value) {
          handleSearch()
        }
      },
      (error) => {
        let errorMessage = 'Unable to get your location. '
        
        switch(error.code) {
          case error.PERMISSION_DENIED:
            errorMessage += 'Please enable location permissions and try again.'
            break
          case error.POSITION_UNAVAILABLE:
            errorMessage += 'Location information is unavailable.'
            break
          case error.TIMEOUT:
            errorMessage += 'Location request timed out.'
            break
          default:
            errorMessage += 'Please enter your location manually.'
        }
        
        alert(errorMessage)
      },
      {
        enableHighAccuracy: false,
        timeout: 10000,
        maximumAge: 30000
      }
    )
  } else {
    alert('Geolocation is not supported by your browser')
  }
}


const handleDateChange = () => {
  // Trigger search when date changes
  if (searchQuery.value || location.value) {
    handleSearch()
  }
}

const handleClickOutside = (event) => {
  if (locationDropdown.value && !locationDropdown.value.contains(event.target)) {
    showLocationDropdown.value = false
  }
}

// Watch for category changes from parent
watch(selectedCategory, (newCategory) => {
  filterSectionsByCategory(newCategory)
})

// Auto-detect location based on IP
const detectLocationByIp = async () => {
  try {
    const response = await fetch('/api/location/detect')
    const data = await response.json()
    
    if (data.status === 'success' && data.data) {
      const locationData = data.data
      // Set to country for broad search
      location.value = locationData.country
    }
  } catch (error) {
    // Silent fail - user can still manually enter location
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  
  // Load cities from API
  loadCities()
  
  // Auto-detect user's location by IP (non-intrusive)
  detectLocationByIp()
  
  // Set default to show all sections
  filterSectionsByCategory('all')
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  clearTimeout(searchTimeout)
})
</script>

<style scoped>
/* Custom date picker styling */
input[type="date"]::-webkit-calendar-picker-indicator {
  cursor: pointer;
  opacity: 0;
  position: absolute;
  right: 0;
  width: 100%;
  height: 100%;
}

input[type="date"] {
  position: relative;
}

/* Loading spinner animation */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>