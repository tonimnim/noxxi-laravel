<template>
  <section class="py-16 px-4 md:px-8 lg:px-12 xl:px-20 bg-gray-50">
    <div class="max-w-7xl mx-auto">
      <!-- Section Header -->
      <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-bold text-[#223338] mb-3">Popular Categories</h2>
        <p class="text-gray-600">Browse events by category</p>
      </div>

      <!-- Categories Grid -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
        <a 
          v-for="category in categories" 
          :key="category.id"
          :href="`/explore?category=${category.slug}`"
          class="group relative h-40 md:h-48 rounded-xl overflow-hidden cursor-pointer transform transition-all duration-300 hover:scale-105"
        >
          <!-- Background Image with Overlay -->
          <div class="absolute inset-0">
            <img 
              v-if="category.image"
              :src="category.image" 
              :alt="category.name"
              class="w-full h-full object-cover"
            >
            <div 
              v-else
              :class="[
                'w-full h-full',
                category.gradient || 'bg-gradient-to-br from-[#305F64] to-[#223338]'
              ]"
            ></div>
            <!-- Dark overlay for text readability -->
            <div class="absolute inset-0 bg-black/40 group-hover:bg-black/50 transition-colors"></div>
          </div>

          <!-- Category Content -->
          <div class="relative h-full flex flex-col items-center justify-center p-4 text-center">
            <!-- Icon -->
            <div class="mb-3">
              <svg 
                v-if="category.icon"
                class="w-10 h-10 md:w-12 md:h-12 text-white"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path 
                  stroke-linecap="round" 
                  stroke-linejoin="round" 
                  stroke-width="2" 
                  :d="category.icon"
                ></path>
              </svg>
              <div v-else class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-white/20"></div>
            </div>

            <!-- Category Name -->
            <h3 class="text-white font-semibold text-lg md:text-xl mb-1">{{ category.name }}</h3>
            
            <!-- Event Count -->
            <p class="text-white/80 text-sm">
              {{ category.count || 0 }} {{ category.count === 1 ? 'event' : 'events' }}
            </p>
          </div>
        </a>
      </div>

      <!-- View All Categories -->
      <div class="text-center mt-10">
        <a 
          href="/categories" 
          class="inline-flex items-center gap-2 text-[#305F64] font-medium hover:opacity-80 transition-opacity"
        >
          View all categories
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </a>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// State
const categories = ref([
  {
    id: 1,
    name: 'Events',
    slug: 'events',
    count: 245,
    gradient: 'bg-gradient-to-br from-purple-500 to-pink-500',
    icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' // Calendar icon
  },
  {
    id: 2,
    name: 'Sports',
    slug: 'sports',
    count: 182,
    gradient: 'bg-gradient-to-br from-green-500 to-teal-500',
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' // Trophy/sports icon
  },
  {
    id: 3,
    name: 'Cinema',
    slug: 'cinema',
    count: 89,
    gradient: 'bg-gradient-to-br from-red-500 to-orange-500',
    icon: 'M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4' // Film icon
  },
  {
    id: 4,
    name: 'Experiences',
    slug: 'experiences',
    count: 156,
    gradient: 'bg-gradient-to-br from-blue-500 to-purple-500',
    icon: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z' // Lightbulb/experience icon
  },
  {
    id: 5,
    name: 'Concerts',
    slug: 'concerts',
    count: 127,
    gradient: 'bg-gradient-to-br from-indigo-500 to-blue-500',
    icon: 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3' // Music note icon
  },
  {
    id: 6,
    name: 'Food & Drink',
    slug: 'food-drink',
    count: 93,
    gradient: 'bg-gradient-to-br from-yellow-500 to-red-500',
    icon: 'M3 3h18v18H3zM12 8v8m-4-4h8' // Food/restaurant icon (simplified)
  },
  {
    id: 7,
    name: 'Arts & Culture',
    slug: 'arts-culture',
    count: 68,
    gradient: 'bg-gradient-to-br from-pink-500 to-purple-500',
    icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' // Image/art icon
  },
  {
    id: 8,
    name: 'Workshops',
    slug: 'workshops',
    count: 74,
    gradient: 'bg-gradient-to-br from-teal-500 to-green-500',
    icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' // Book/learning icon
  }
])

// Fetch actual category counts from API
const fetchCategoryCounts = async () => {
  try {
    const response = await fetch('/api/categories/popular')
    const data = await response.json()
    if (data.status === 'success' && data.data) {
      // Update counts from API while keeping our UI data
      categories.value = categories.value.map(cat => {
        const apiCat = data.data.find(c => c.slug === cat.slug)
        if (apiCat) {
          cat.count = apiCat.event_count || 0
        }
        return cat
      })
    }
  } catch (error) {
    console.error('Error fetching category counts:', error)
  }
}

// Lifecycle
onMounted(() => {
  fetchCategoryCounts()
})
</script>