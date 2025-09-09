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
          <a href="/" :class="['font-silk text-2xl md:text-3xl tracking-tight hover:opacity-80 transition-opacity', forceWhiteText && !hasScrolled ? 'text-white' : 'text-[#223338]']">
            NOXXI
          </a>
        </div>


        <!-- Right Side Actions -->
        <div class="flex items-center gap-2 md:gap-4 lg:mr-12 xl:mr-20">
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
        <div class="px-6 pt-2 pb-3">
          <a href="/login" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
            Sign in
          </a>
        </div>
      </div>
    </transition>
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useAuthStore } from '../../stores/auth'

// Store
const authStore = useAuthStore()

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
let lastScrollY = 0

// Computed
const authLoading = computed(() => !authStore.initialized || authStore.loading)
const isAuthenticated = computed(() => authStore.isAuthenticated)

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


// Handle logout
const handleLogout = async () => {
  try {
    // Use auth store logout which handles both API and web logout
    await authStore.logout()
  } catch (error) {
    console.error('Logout error:', error)
    // Fallback to direct logout if store fails
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
  }
}

// Scroll to featured section
const scrollToFeatured = (event) => {
  event.preventDefault()
  
  // Close mobile menu if open
  showMobileMenu.value = false
  
  // Find the featured section - it's right after the hero
  const featuredSection = document.querySelector('#featured-events') || 
                         document.querySelector('.featured-section') ||
                         document.querySelector('[data-section="featured"]')
  
  if (featuredSection) {
    featuredSection.scrollIntoView({ 
      behavior: 'smooth', 
      block: 'start' 
    })
  } else {
    // If featured section not found, scroll to a position below hero (approximately 100vh)
    window.scrollTo({
      top: window.innerHeight - 80, // Subtract header height
      behavior: 'smooth'
    })
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  window.addEventListener('scroll', handleScroll)
  window.addEventListener('content-scroll', handleContentScroll)
  // Check initial scroll position
  handleScroll()
  // Auth is initialized before component mounts
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('scroll', handleScroll)
  window.removeEventListener('content-scroll', handleContentScroll)
})
</script>