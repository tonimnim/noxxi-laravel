<template>
  <div class="bg-white rounded-2xl p-6 w-80 shadow-xl">
    <h2 class="text-xl font-semibold mb-6">Find Your Next Experience</h2>
    
    <!-- Search Form -->
    <div class="space-y-4">
      <!-- Search Input -->
      <div>
        <label class="text-xs text-gray-500 uppercase tracking-wider mb-2 block">Search</label>
        <div class="relative">
          <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
          <input 
            v-model="searchTerm"
            type="text" 
            placeholder="What are you looking for?"
            class="w-full pl-10 pr-3 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-black transition-colors"
          >
        </div>
      </div>
      
      <!-- Location Input -->
      <div>
        <label class="text-xs text-gray-500 uppercase tracking-wider mb-2 block">Location</label>
        <div class="relative">
          <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          <input 
            v-model="location"
            type="text" 
            placeholder="Where?"
            class="w-full pl-10 pr-3 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-black transition-colors"
          >
        </div>
      </div>
      
      <!-- Date Input -->
      <div>
        <label class="text-xs text-gray-500 uppercase tracking-wider mb-2 block">Date</label>
        <div class="relative">
          <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <input 
            v-model="date"
            type="date" 
            placeholder="mm/dd/yyyy"
            class="w-full pl-10 pr-3 py-3 border border-gray-200 rounded-lg focus:outline-none focus:border-black transition-colors cursor-pointer date-input"
          >
        </div>
      </div>
      
      <!-- Search Button -->
      <button 
        @click="handleSearch"
        class="w-full bg-black text-white py-3 rounded-lg font-medium hover:bg-gray-800 transition-colors mt-6"
      >
        Search
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

// State
const searchTerm = ref('')
const location = ref('')
const date = ref('')

// Methods
const handleSearch = () => {
  // Emit search event with data
  console.log('Searching:', {
    searchTerm: searchTerm.value,
    location: location.value,
    date: date.value
  })
  // TODO: Implement actual search logic
}
</script>

<style scoped>
/* Hide the native calendar icon in date inputs */
.date-input::-webkit-calendar-picker-indicator {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background: transparent;
  color: transparent;
  cursor: pointer;
}

/* For Firefox */
.date-input::-moz-calendar-picker-indicator {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background: transparent;
  color: transparent;
  cursor: pointer;
}
</style>