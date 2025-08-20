<template>
  <!-- Floating Search Button - Mobile Only -->
  <div class="md:hidden fixed bottom-4 right-4 z-50">
    <button 
      @click="openSearch"
      class="bg-[#305F64] text-white p-4 rounded-full shadow-lg hover:bg-[#254a4f] transition-colors"
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
      </svg>
    </button>
  </div>
</template>

<script setup>
const openSearch = () => {
  // This could open a modal or navigate to a search page
  console.log('Open search modal or page')
  // For now, just log - can be implemented later
}
</script>