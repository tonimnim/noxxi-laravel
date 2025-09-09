<template>
  <div 
    class="group cursor-pointer"
    @click="goToEventDetails(event)"
  >
    <div class="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-xl transition-all duration-300">
      <!-- Event Image -->
      <div class="relative h-40 sm:h-48 lg:h-44 xl:h-48 overflow-hidden">
        <img 
          v-if="event.cover_image_url"
          :src="event.cover_image_url" 
          :alt="event.title"
          class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
        >
        <div v-else class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300"></div>

        <!-- Price Badge -->
        <div class="absolute top-3 right-3">
          <span class="px-2 lg:px-3 py-0.5 lg:py-1 bg-[#305F64]/90 backdrop-blur-sm text-white rounded-full text-xs lg:text-sm font-medium">
            {{ formatPrice(event) }}
          </span>
        </div>

        <!-- Category -->
        <div class="absolute bottom-3 left-3">
          <span class="px-2 lg:px-3 py-0.5 lg:py-1 bg-white/90 backdrop-blur-sm rounded-full text-[10px] lg:text-xs font-medium text-[#223338]">
            {{ event.category?.name || 'Event' }}
          </span>
        </div>
      </div>

      <!-- Event Details -->
      <div class="p-4 lg:p-4 xl:p-5">
        <!-- Title -->
        <h3 class="font-bold text-base lg:text-lg xl:text-xl text-[#223338] line-clamp-2 group-hover:text-[#305F64] transition-colors">
          {{ event.title }}
        </h3>

        <!-- Date -->
        <div class="text-sm text-gray-600 mb-1">
          {{ formatDate(event.event_date) }}
        </div>

        <!-- Location -->
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-3">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          <span class="line-clamp-1">{{ event.venue_address }}</span>
        </div>

        <!-- Description Extract -->
        <p class="text-sm text-gray-700 line-clamp-2">
          {{ event.description ? (event.description.length > 100 ? event.description.substring(0, 100) + '...' : event.description) : '' }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
// Props
const props = defineProps({
  event: {
    type: Object,
    required: true
  }
})

// Methods - exactly like EventsSection
const formatPrice = (event) => {
  if (!event.min_price || event.min_price === 0) {
    return 'Free'
  }
  const currency = event.currency || 'USD'
  const price = parseFloat(event.min_price).toLocaleString()
  return `${currency} ${price}`
}

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

const goToEventDetails = (event) => {
  const identifier = event.slug || event.id
  window.location.href = `/listings/${identifier}`
}
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