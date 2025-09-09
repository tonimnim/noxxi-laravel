<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-md">
      <div class="bg-white py-8 px-6 shadow-lg rounded-lg sm:px-10">
        <!-- Logo and Header -->
        <div class="mb-8">
          <a href="/" class="flex items-center justify-center">
            <h1 class="font-silk text-3xl font-bold text-[#305F64]">NOXXI</h1>
          </a>
          <h2 class="mt-6 text-2xl font-semibold text-gray-900 text-center">Become an Organizer</h2>
          <p class="mt-2 text-sm text-gray-600 text-center">
            Start creating and managing events
          </p>
        </div>

        <!-- Form -->
        <form class="space-y-5" @submit.prevent="handleRegister">
          <!-- Business Name Field -->
          <div>
            <label for="business_name" class="block text-sm font-medium text-gray-700 mb-1">
              Business/Organization name
            </label>
            <input
              id="business_name"
              name="business_name"
              type="text"
              v-model="form.business_name"
              required
              class="appearance-none block w-full px-3 py-2.5 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#305F64] focus:border-transparent transition-colors"
              placeholder="Your business or organization name"
            >
          </div>

          <!-- Full Name Field -->
          <div>
            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">
              Your full name
            </label>
            <input
              id="full_name"
              name="full_name"
              type="text"
              v-model="form.full_name"
              autocomplete="name"
              required
              class="appearance-none block w-full px-3 py-2.5 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#305F64] focus:border-transparent transition-colors"
              placeholder="John Doe"
            >
          </div>

          <!-- Email Field -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
              Business email
            </label>
            <input
              id="email"
              name="email"
              type="email"
              v-model="form.email"
              autocomplete="email"
              required
              class="appearance-none block w-full px-3 py-2.5 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#305F64] focus:border-transparent transition-colors"
              placeholder="business@example.com"
            >
          </div>

          <!-- Phone Number with Country Picker -->
          <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
              Phone number
            </label>
            <phone-input
              v-model="form.phone_number"
              :required="true"
              placeholder="712 345 678"
              @validate="phoneValidation = $event"
            />
          </div>

          <!-- Password Field -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
              Password
            </label>
            <div class="relative">
              <input
                :type="showPassword ? 'text' : 'password'"
                id="password"
                name="password"
                v-model="form.password"
                autocomplete="new-password"
                required
                minlength="4"
                class="appearance-none block w-full px-3 py-2.5 pr-10 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#305F64] focus:border-transparent transition-colors"
                placeholder="Minimum 4 characters"
              >
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <svg v-if="!showPassword" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Terms and Conditions -->
          <div class="flex items-start">
            <input
              id="terms"
              name="terms"
              type="checkbox"
              v-model="form.terms"
              required
              class="h-4 w-4 text-[#305F64] focus:ring-[#305F64] border-gray-300 rounded mt-0.5"
            >
            <label for="terms" class="ml-2 block text-sm text-gray-700">
              I agree to the 
              <a href="/terms" class="font-medium text-[#305F64] hover:text-[#204044]">Terms</a>,
              <a href="/privacy" class="font-medium text-[#305F64] hover:text-[#204044]">Privacy Policy</a>, and
              <a href="/organizer-agreement" class="font-medium text-[#305F64] hover:text-[#204044]">Organizer Agreement</a>
            </label>
          </div>

          <!-- Error Message -->
          <div v-if="error" class="rounded-lg bg-red-50 border border-red-200 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-red-800">{{ error }}</p>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div>
            <button
              type="submit"
              :disabled="loading || !form.business_name || !form.full_name || !form.email || !form.password || !form.phone_number || !form.terms"
              class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-[#305F64] hover:bg-[#204044] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#305F64] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="loading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Creating organizer account...
              </span>
              <span v-else>Create organizer account</span>
            </button>
          </div>

          <!-- Sign In Link -->
          <div class="text-center mt-6">
            <p class="text-sm text-gray-600">
              Already have an organizer account?
              <a href="/login/organizer" class="font-medium text-[#305F64] hover:text-[#204044]">
                Sign in
              </a>
            </p>
          </div>

          <!-- User Link -->
          <div class="text-center">
            <p class="text-sm text-gray-600">
              Looking to attend events?
              <a href="/register" class="font-medium text-[#305F64] hover:text-[#204044]">
                Register as attendee
              </a>
            </p>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const form = ref({
  business_name: '',
  full_name: '',
  email: '',
  password: '',
  phone_number: '',
  password_confirmation: '',
  role: 'organizer',
  terms: false
})

const error = ref('')
const loading = ref(false)
const showPassword = ref(false)
const phoneValidation = ref(null)

const handleRegister = async () => {
  error.value = ''
  loading.value = true

  // Check if phone is valid
  if (!phoneValidation.value || !phoneValidation.value.isValid) {
    error.value = 'Please enter a valid phone number'
    loading.value = false
    return
  }

  // Set password confirmation to match password
  form.value.password_confirmation = form.value.password

  try {
    const response = await fetch('/auth/web/register-organizer', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        business_name: form.value.business_name,
        full_name: form.value.full_name,
        email: form.value.email,
        password: form.value.password,
        phone_number: form.value.phone_number
      }),
      credentials: 'same-origin'
    })

    const data = await response.json()

    if (data.status === 'success') {
      // Store user data
      localStorage.setItem('user', JSON.stringify(data.user))
      if (data.organizer) {
        localStorage.setItem('organizer', JSON.stringify(data.organizer))
      }
      
      // Redirect to email verification
      window.location.href = '/email/verify'
    } else {
      // Show user-friendly error message
      error.value = data.message || 'Registration failed. Please try again.'
    }
  } catch (err) {
    error.value = 'An error occurred. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>

/* Custom styles for vue-tel-input */
</style>