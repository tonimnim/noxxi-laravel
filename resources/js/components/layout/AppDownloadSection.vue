<template>
  <div class="bg-white py-12 px-4 md:px-8 lg:px-12 xl:px-20 mt-0.5">
    <div class="bg-[#DCE1E2] rounded-3xl py-12 px-8 text-center">
      <!-- Title -->
      <h2 class="text-2xl md:text-3xl font-bold text-[#223338] mb-3">
        Download the Noxxi app
      </h2>
      
      <!-- Subtitle -->
      <p class="text-[#223338] opacity-80 text-lg mb-8">
        Discover your favorite events with ease
      </p>
      
      <!-- Download Buttons -->
      <div class="flex flex-wrap justify-center gap-4">
        <!-- Google Play Button -->
        <a href="#" class="inline-block bg-[#223338] rounded-lg px-6 py-3 hover:bg-[#305F64] transition-colors">
          <div class="flex items-center gap-3">
            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none">
              <path d="M3 20.5V3.5C3 2.91 3.34 2.39 3.84 2.15L13.69 12L3.84 21.85C3.34 21.6 3 21.09 3 20.5Z" fill="#4CAF50"/>
              <path d="M13.69 12L3.84 2.15C4.04 2.05 4.27 2 4.5 2C4.65 2 4.8 2.03 4.94 2.08L16.81 8.69L13.69 12Z" fill="#FFC107"/>
              <path d="M13.69 12L16.81 15.31L4.94 21.92C4.8 21.97 4.65 22 4.5 22C4.27 22 4.04 21.95 3.84 21.85L13.69 12Z" fill="#F44336"/>
              <path d="M13.69 12L16.81 8.69L20.16 10.6C20.68 10.89 21 11.41 21 12C21 12.59 20.68 13.11 20.16 13.4L16.81 15.31L13.69 12Z" fill="#2196F3"/>
            </svg>
            <div class="text-left">
              <div class="text-xs text-gray-200">GET IT ON</div>
              <div class="text-white font-semibold">Google Play</div>
            </div>
          </div>
        </a>
        
        <!-- App Store Button -->
        <a href="#" class="inline-block bg-[#223338] rounded-lg px-6 py-3 hover:bg-[#305F64] transition-colors">
          <div class="flex items-center gap-3">
            <svg class="w-8 h-8" viewBox="0 0 24 24" fill="white">
              <path d="M18.71 19.5C17.88 20.74 17 21.95 15.66 21.97C14.32 22 13.89 21.18 12.37 21.18C10.84 21.18 10.37 21.95 9.09997 22C7.78997 22.05 6.79997 20.68 5.95997 19.47C4.24997 16.97 2.93997 12.45 4.69997 9.39C5.56997 7.87 7.12997 6.91 8.81997 6.88C10.1 6.86 11.32 7.75 12.11 7.75C12.89 7.75 14.37 6.68 15.92 6.84C16.57 6.87 18.39 7.1 19.56 8.82C19.47 8.88 17.39 10.1 17.41 12.63C17.44 15.65 20.06 16.66 20.09 16.67C20.06 16.74 19.67 18.11 18.71 19.5ZM13 3.5C13.73 2.67 14.94 2.04 15.94 2C16.07 3.17 15.6 4.35 14.9 5.19C14.21 6.04 13.07 6.7 11.95 6.61C11.8 5.46 12.36 4.26 13 3.5Z"/>
            </svg>
            <div class="text-left">
              <div class="text-xs text-gray-200">Download on the</div>
              <div class="text-white font-semibold">App Store</div>
            </div>
          </div>
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
// Component is static, no reactive data needed
</script>

<style scoped>
/* Add any specific styles if needed */
</style>