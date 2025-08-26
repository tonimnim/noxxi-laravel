<template>
  <header 
    :class="[
      'fixed left-0 right-0 z-50 transition-all duration-300',
      isVisible ? 'translate-y-0' : '-translate-y-full',
      hasScrolled 
        ? 'bg-white/95 backdrop-blur-lg shadow-lg top-0' 
        : 'bg-transparent top-3 md:top-5'
    ]"
  >
    <div class="w-full px-4 md:px-6 lg:px-12">
      <div class="flex items-center justify-between h-16 md:h-20">
        <!-- Logo -->
        <div class="flex items-center lg:ml-[180px] xl:ml-[210px]">
          <a href="/" :class="['logo-briski text-2xl md:text-3xl tracking-tight hover:opacity-80 transition-opacity', forceWhiteText && !hasScrolled ? 'text-white' : 'text-[#223338]']">
            NOXXI
          </a>
        </div>

        <!-- Center Navigation - Desktop Only -->
        <nav class="hidden lg:flex items-center justify-center absolute left-1/2 transform -translate-x-1/2">
          <div class="flex items-center gap-6 xl:gap-10">
            <a href="/explore" :class="['text-sm font-medium hover:opacity-80 transition-opacity', forceWhiteText && !hasScrolled ? 'text-white' : 'text-[#223338]']">
              Explore
            </a>
            <a href="/sell-tickets" :class="['text-sm font-medium hover:opacity-80 transition-opacity', forceWhiteText && !hasScrolled ? 'text-white' : 'text-[#223338]']">
              Sell tickets
            </a>
            <a href="/enterprise" :class="['text-sm font-medium hover:opacity-80 transition-opacity', forceWhiteText && !hasScrolled ? 'text-white' : 'text-[#223338]']">
              Enterprise
            </a>
            <a href="/help" :class="['text-sm font-medium hover:opacity-80 transition-opacity', forceWhiteText && !hasScrolled ? 'text-white' : 'text-[#223338]']">
              Help
            </a>
          </div>
        </nav>

        <!-- Right Side Actions -->
        <div class="flex items-center gap-2 md:gap-4 lg:mr-12 xl:mr-20">
          <!-- Language Selector -->
          <div class="relative hidden md:block" ref="languageDropdown">
            <button 
              @click="toggleLanguageDropdown"
              :class="['text-sm font-medium hover:opacity-80 transition-opacity flex items-center gap-2', forceWhiteText && !hasScrolled ? 'text-white' : 'text-[#223338]']"
            >
              <!-- World Globe Icon -->
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              {{ currentLanguage.code }}
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            
            <!-- Language Dropdown -->
            <transition
              enter-active-class="transition ease-out duration-100"
              enter-from-class="transform opacity-0 scale-95"
              enter-to-class="transform opacity-100 scale-100"
              leave-active-class="transition ease-in duration-75"
              leave-from-class="transform opacity-100 scale-100"
              leave-to-class="transform opacity-0 scale-95"
            >
              <div 
                v-if="showLanguageDropdown"
                class="absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
              >
                <div class="py-1">
                  <button 
                    v-for="lang in languages" 
                    :key="lang.code"
                    @click="selectLanguage(lang)"
                    :class="[
                      'w-full text-left px-4 py-2 text-sm hover:bg-gray-100 flex items-center justify-between transition-colors',
                      currentLanguage.code === lang.code ? 'bg-gray-50 text-black font-medium' : 'text-gray-700'
                    ]"
                  >
                    <span>{{ lang.name }}</span>
                    <span class="text-gray-500 text-xs">{{ lang.native }}</span>
                  </button>
                </div>
              </div>
            </transition>
          </div>

          <!-- Auth Section - Hidden on small mobile -->
          <div v-if="!isAuthenticated" class="hidden sm:block">
            <a href="/login" :class="['text-sm font-medium hover:opacity-80 transition-opacity', forceWhiteText && !hasScrolled ? 'text-white' : 'text-[#223338]']">
              Sign in
            </a>
          </div>
          
          <!-- Profile Icon for Logged In Users -->
          <div v-else class="hidden sm:flex items-center gap-3">
            <a 
              href="/account" 
              :class="['flex items-center gap-2 hover:opacity-80 transition-opacity', forceWhiteText && !hasScrolled ? 'text-white' : 'text-[#223338]']"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
              <span class="text-sm font-medium">My Account</span>
            </a>
            <button 
              @click="handleLogout"
              :class="['text-sm hover:opacity-80 transition-opacity', forceWhiteText && !hasScrolled ? 'text-white' : 'text-[#223338]']"
            >
              Logout
            </button>
          </div>


          <!-- Mobile Menu Button -->
          <button 
            @click="showMobileMenu = !showMobileMenu"
            :class="['lg:hidden p-1.5 sm:p-2 rounded-md hover:opacity-80', forceWhiteText && !hasScrolled ? 'text-white' : 'text-[#223338]']"
          >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Menu -->
    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div 
        v-if="showMobileMenu"
        class="lg:hidden absolute top-20 left-0 right-0 bg-white/95 backdrop-blur-lg shadow-lg"
      >
        <div class="px-6 pt-2 pb-3 space-y-1">
          <a href="/explore" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
            Explore
          </a>
          <a href="/sell-tickets" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
            Sell tickets
          </a>
          <a href="/enterprise" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
            Enterprise
          </a>
          <a href="/help" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
            Help
          </a>
        </div>
        <div class="pt-4 pb-3 border-t border-gray-200/50">
          <div class="px-6 space-y-2">
            <a href="/login" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
              Sign in
            </a>
          </div>
        </div>
      </div>
    </transition>
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

// Props
const props = defineProps({
  forceWhiteText: {
    type: Boolean,
    default: false
  }
})

// State
const showLanguageDropdown = ref(false)
const showMobileMenu = ref(false)
const languageDropdown = ref(null)
const hasScrolled = ref(false)
const isVisible = ref(true)
const isAuthenticated = ref(false)
let lastScrollY = 0

const languages = [
  { code: 'EN', name: 'English', native: 'English' },
  { code: 'FR', name: 'French', native: 'Français' },
  { code: 'ES', name: 'Spanish', native: 'Español' },
  { code: 'ZH', name: 'Chinese', native: '中文' },
]

const currentLanguage = ref(languages[0]) // Default to English

// Methods
const toggleLanguageDropdown = () => {
  showLanguageDropdown.value = !showLanguageDropdown.value
}

const selectLanguage = (lang) => {
  currentLanguage.value = lang
  showLanguageDropdown.value = false
  // Here you would typically trigger i18n language change
}

const handleClickOutside = (event) => {
  if (languageDropdown.value && !languageDropdown.value.contains(event.target)) {
    showLanguageDropdown.value = false
  }
}

const handleScroll = () => {
  const currentScrollY = window.scrollY
  
  // Add background when scrolled more than 100px
  hasScrolled.value = currentScrollY > 100
  
  // Don't hide/show header for small scroll amounts
  if (Math.abs(currentScrollY - lastScrollY) < 5) {
    return
  }
  
  // Show header when scrolling up or at the top
  if (currentScrollY < lastScrollY || currentScrollY < 10) {
    isVisible.value = true
  } 
  // Hide header when scrolling down (and not near top)
  else if (currentScrollY > lastScrollY && currentScrollY > 100) {
    isVisible.value = false
  }
  
  lastScrollY = currentScrollY
}

const handleContentScroll = (event) => {
  const { scrollY, direction } = event.detail
  
  // Add background when scrolled more than 100px
  hasScrolled.value = scrollY > 100
  
  // Show header when scrolling up or at the top
  if (direction === 'up' || scrollY < 10) {
    isVisible.value = true
  } 
  // Hide header when scrolling down (and not near top)
  else if (direction === 'down' && scrollY > 100) {
    isVisible.value = false
  }
}

// Check authentication
const checkAuth = async () => {
  try {
    const response = await fetch('/auth/web/check', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include'
    })
    
    if (response.ok) {
      const data = await response.json()
      isAuthenticated.value = data.authenticated || false
    }
  } catch (error) {
    isAuthenticated.value = false
  }
}

// Handle logout
const handleLogout = async () => {
  try {
    const response = await fetch('/auth/web/logout', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include'
    })
    
    if (response.ok) {
      window.location.href = '/'
    }
  } catch (error) {
    console.error('Logout error:', error)
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  window.addEventListener('scroll', handleScroll)
  window.addEventListener('content-scroll', handleContentScroll)
  // Check initial scroll position
  handleScroll()
  // Check authentication status
  checkAuth()
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('scroll', handleScroll)
  window.removeEventListener('content-scroll', handleContentScroll)
})
</script>

<style scoped>
.logo-briski {
  font-family: 'Briski', serif;
}
</style>