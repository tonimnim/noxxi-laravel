<template>
  <div class="text-center py-12">
    <div class="mx-auto w-24 h-24 mb-4">
      <svg class="w-full h-full text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path v-if="category === 'cinema'" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
          d="M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4M8 4h8a1 1 0 011 1v14a1 1 0 01-1 1H8a1 1 0 01-1-1V5a1 1 0 011-1z"/>
        <path v-else-if="category === 'sports'" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M3 3v18h18M7 12l3-3 4 4 5-5"/>
        <path v-else-if="category === 'experiences'" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
      </svg>
    </div>
    <h3 class="text-lg font-medium text-gray-900 mb-1">{{ title }}</h3>
    <p class="text-sm text-gray-500 mb-4 max-w-sm mx-auto">{{ message }}</p>
    <a 
      href="/register/organizer" 
      class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-[#305F64] hover:bg-[#254a4f] transition-colors"
    >
      <svg class="mr-2 -ml-0.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
      </svg>
      List Your {{ categoryLabel }}
    </a>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  category: {
    type: String,
    required: true,
    validator: (value) => ['events', 'sports', 'cinema', 'experiences'].includes(value)
  }
})

const title = computed(() => {
  const titles = {
    events: 'No Events Available',
    sports: 'No Sports Events Yet',
    cinema: 'No Movies Showing',
    experiences: 'No Experiences Available'
  }
  return titles[props.category] || 'No Items Available'
})

const message = computed(() => {
  const messages = {
    events: 'Be the first to create an amazing event in your area.',
    sports: 'Start listing sports events and connect with fans.',
    cinema: 'Add movie screenings and bring entertainment to your community.',
    experiences: 'Share unique experiences and adventures with others.'
  }
  return messages[props.category] || 'Be the first to add something here.'
})

const categoryLabel = computed(() => {
  const labels = {
    events: 'Event',
    sports: 'Sports Event',
    cinema: 'Movie',
    experiences: 'Experience'
  }
  return labels[props.category] || 'Event'
})
</script>