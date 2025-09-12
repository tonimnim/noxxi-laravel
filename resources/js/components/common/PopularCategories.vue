<template>
  <section class="py-16 px-4 md:px-8 lg:px-12 xl:px-20">
    <div class="max-w-7xl mx-auto">
      <!-- Section Header -->
      <div class="mb-8">
        <h2 class="text-2xl md:text-3xl font-semibold text-[#223338]">Top experiences near you</h2>
      </div>

      <!-- Categories Carousel Container -->
      <div class="relative">
        <!-- Carousel -->
        <div 
          ref="carousel"
          class="flex gap-4 overflow-x-auto scroll-smooth pb-4"
          :style="{ scrollSnapType: 'x mandatory' }"
        >
          <!-- Category Cards -->
          <a 
            v-for="category in categories" 
            :key="category.id"
            :href="`/${category.slug}`"
            class="flex-none w-[320px] md:w-[380px] lg:w-[420px] group"
            style="scroll-snap-align: start;"
          >
            <div class="relative h-[240px] md:h-[280px] rounded-2xl overflow-hidden cursor-pointer">
              <!-- Background Image -->
              <img 
                v-if="category.image"
                :src="category.image" 
                :alt="category.name"
                class="w-full h-full object-cover object-center transition-transform duration-700 group-hover:scale-110"
                style="image-rendering: -webkit-optimize-contrast;"
                @error="handleImageError($event, category)"
              >
              <div 
                v-else
                :class="[
                  'w-full h-full',
                  category.gradient || 'bg-gradient-to-br from-[#305F64] to-[#223338]'
                ]"
              ></div>
              
              <!-- Gradient Overlay -->
              <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
              
              <!-- Play Button (for video-like appearance) -->
              <div v-if="category.hasVideo" class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 opacity-80 group-hover:opacity-100 transition-opacity">
                <div class="w-14 h-14 bg-white/90 rounded-full flex items-center justify-center">
                  <svg class="w-6 h-6 text-[#223338] ml-1" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                  </svg>
                </div>
              </div>
              
              <!-- Category Name -->
              <div class="absolute bottom-0 left-0 right-0 p-6">
                <h3 class="text-white font-bold text-xl md:text-2xl">{{ category.name }}</h3>
              </div>
            </div>
          </a>
        </div>

        <!-- Navigation Arrows -->
        <button 
          @click="scrollCarousel('left')"
          class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 bg-white rounded-full shadow-lg p-3 hover:shadow-xl transition-shadow hidden md:block"
        >
          <svg class="w-5 h-5 text-[#223338]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        
        <button 
          @click="scrollCarousel('right')"
          class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 bg-white rounded-full shadow-lg p-3 hover:shadow-xl transition-shadow hidden md:block"
        >
          <svg class="w-5 h-5 text-[#223338]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </button>

        <!-- Dots Indicator -->
        <div class="flex justify-center gap-2 mt-6">
          <button 
            v-for="(dot, index) in Math.ceil(categories.length / itemsPerView)" 
            :key="index"
            @click="scrollToPage(index)"
            :class="[
              'w-2 h-2 rounded-full transition-all duration-300',
              currentPage === index ? 'bg-[#305F64] w-8' : 'bg-gray-300'
            ]"
          ></button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'

// Refs
const carousel = ref(null)
const currentPage = ref(0)
const itemsPerView = ref(3)

// Categories with test comedy image
const categories = ref([
  {
    id: 1,
    name: 'Comedy Shows',
    slug: 'comedy-shows',
    image: 'https://i.postimg.cc/SnYqmn3G/comedian-2152801-640.jpg',
    gradient: 'bg-gradient-to-br from-purple-500 to-pink-500'
  },
  {
    id: 2,
    name: 'Concerts',
    slug: 'concerts',
    gradient: 'bg-gradient-to-br from-indigo-500 to-blue-500'
  },
  {
    id: 3,
    name: 'Conferences & Workshops',
    slug: 'conferences-workshops',
    gradient: 'bg-gradient-to-br from-teal-500 to-green-500'
  },
  {
    id: 4,
    name: 'Festivals',
    slug: 'festivals',
    gradient: 'bg-gradient-to-br from-orange-500 to-red-500'
  },
  {
    id: 5,
    name: 'Theater & Plays',
    slug: 'theater-plays',
    gradient: 'bg-gradient-to-br from-purple-500 to-indigo-500'
  },
  {
    id: 6,
    name: 'Adventure',
    slug: 'adventure',
    gradient: 'bg-gradient-to-br from-green-500 to-teal-500'
  },
  {
    id: 7,
    name: 'Art Exhibitions',
    slug: 'art-exhibitions',
    gradient: 'bg-gradient-to-br from-pink-500 to-purple-500'
  },
  {
    id: 8,
    name: 'Nightlife',
    slug: 'nightlife',
    gradient: 'bg-gradient-to-br from-purple-600 to-pink-600'
  },
  {
    id: 9,
    name: 'Wellness',
    slug: 'wellness',
    gradient: 'bg-gradient-to-br from-teal-400 to-blue-400'
  },
  {
    id: 10,
    name: 'Basketball',
    slug: 'basketball',
    gradient: 'bg-gradient-to-br from-orange-500 to-red-600'
  },
  {
    id: 11,
    name: 'Combat Sports',
    slug: 'combat',
    gradient: 'bg-gradient-to-br from-red-600 to-gray-800'
  },
  {
    id: 12,
    name: 'Football',
    slug: 'football',
    gradient: 'bg-gradient-to-br from-green-600 to-green-800'
  },
  {
    id: 13,
    name: 'Motorsports',
    slug: 'motorsports',
    gradient: 'bg-gradient-to-br from-gray-700 to-red-600'
  },
  {
    id: 14,
    name: 'Pool',
    slug: 'pool',
    gradient: 'bg-gradient-to-br from-green-700 to-blue-700'
  },
  {
    id: 15,
    name: 'Rugby',
    slug: 'rugby',
    gradient: 'bg-gradient-to-br from-green-600 to-yellow-600'
  }
])

// Methods
const scrollCarousel = (direction) => {
  if (!carousel.value) return
  
  const scrollAmount = carousel.value.offsetWidth
  if (direction === 'left') {
    carousel.value.scrollLeft -= scrollAmount
  } else {
    carousel.value.scrollLeft += scrollAmount
  }
}

const scrollToPage = (pageIndex) => {
  if (!carousel.value) return
  
  const scrollAmount = carousel.value.offsetWidth * pageIndex
  carousel.value.scrollLeft = scrollAmount
}

const handleScroll = () => {
  if (!carousel.value) return
  
  const scrollPosition = carousel.value.scrollLeft
  const pageWidth = carousel.value.offsetWidth
  currentPage.value = Math.round(scrollPosition / pageWidth)
}

const handleImageError = (event, category) => {
  // If image fails to load, hide it and show gradient instead
  event.target.style.display = 'none'
  console.error(`Failed to load image for ${category.name}`)
}

const updateItemsPerView = () => {
  const width = window.innerWidth
  if (width < 768) {
    itemsPerView.value = 1
  } else if (width < 1024) {
    itemsPerView.value = 2
  } else {
    itemsPerView.value = 3
  }
}

// Auto-scroll animation
let autoScrollInterval = null
const startAutoScroll = () => {
  autoScrollInterval = setInterval(() => {
    if (!carousel.value) return
    
    const maxScroll = carousel.value.scrollWidth - carousel.value.offsetWidth
    const currentScroll = carousel.value.scrollLeft
    
    if (currentScroll >= maxScroll - 10) {
      // Reset to beginning
      carousel.value.scrollLeft = 0
    } else {
      // Scroll to next item
      scrollCarousel('right')
    }
  }, 5000) // Change slide every 5 seconds
}

const stopAutoScroll = () => {
  if (autoScrollInterval) {
    clearInterval(autoScrollInterval)
    autoScrollInterval = null
  }
}

// Lifecycle
onMounted(() => {
  if (carousel.value) {
    carousel.value.addEventListener('scroll', handleScroll)
    carousel.value.addEventListener('mouseenter', stopAutoScroll)
    carousel.value.addEventListener('mouseleave', startAutoScroll)
  }
  
  window.addEventListener('resize', updateItemsPerView)
  updateItemsPerView()
  startAutoScroll()
})

onUnmounted(() => {
  if (carousel.value) {
    carousel.value.removeEventListener('scroll', handleScroll)
    carousel.value.removeEventListener('mouseenter', stopAutoScroll)
    carousel.value.removeEventListener('mouseleave', startAutoScroll)
  }
  
  window.removeEventListener('resize', updateItemsPerView)
  stopAutoScroll()
})
</script>

<style scoped>
/* Hide scrollbar but keep functionality */
.overflow-x-auto::-webkit-scrollbar {
  display: none;
}

.overflow-x-auto {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>