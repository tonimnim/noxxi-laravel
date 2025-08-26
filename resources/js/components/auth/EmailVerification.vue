<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-md px-4 sm:px-6">
      <div class="bg-white py-8 px-6 shadow-lg rounded-lg sm:px-10">
        <!-- Logo and Header -->
        <div class="text-center mb-8">
          <a href="/" class="inline-flex items-center justify-center">
            <h1 class="logo-briski text-3xl font-bold text-[#305F64]">NOXXI</h1>
          </a>
          
          <div class="mt-6">
            <svg class="mx-auto h-12 w-12 text-[#305F64]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76" />
            </svg>
          </div>
          
          <h2 class="mt-4 text-2xl font-semibold text-gray-900">Verify your email</h2>
          <p class="mt-2 text-sm text-gray-600">
            We've sent a verification code to<br>
            <span class="font-medium text-gray-900">{{ userEmail }}</span>
          </p>
        </div>

        <!-- OTP Input Form -->
        <form @submit.prevent="handleVerification" class="space-y-6">
          <div>
            <label for="otp" class="block text-sm font-medium text-gray-700 mb-2">
              Enter verification code
            </label>
            <div class="flex justify-center space-x-2">
              <input
                v-for="(digit, index) in otpDigits"
                :key="index"
                :ref="`otpInput${index}`"
                type="text"
                maxlength="1"
                v-model="otpDigits[index]"
                @input="handleOtpInput(index)"
                @keydown.backspace="handleBackspace(index)"
                @paste="handlePaste"
                class="w-12 h-12 text-center text-lg font-semibold border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#305F64] focus:border-transparent"
                :class="{'border-red-500': error}"
                pattern="[0-9]"
                inputmode="numeric"
              >
            </div>
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

          <!-- Success Message -->
          <div v-if="success" class="rounded-lg bg-green-50 border border-green-200 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-green-800">{{ success }}</p>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div>
            <button
              type="submit"
              :disabled="loading || otpDigits.join('').length !== 6"
              class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-[#305F64] hover:bg-[#204044] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#305F64] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="loading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Verifying...
              </span>
              <span v-else>Verify Email</span>
            </button>
          </div>

          <!-- Resend Code -->
          <div class="text-center">
            <p class="text-sm text-gray-600">
              Didn't receive the code?
              <button
                type="button"
                @click="resendCode"
                :disabled="resendLoading || resendCooldown > 0"
                class="font-medium text-[#305F64] hover:text-[#204044] disabled:text-gray-400 disabled:cursor-not-allowed"
              >
                <span v-if="resendCooldown > 0">Resend in {{ resendCooldown }}s</span>
                <span v-else>Resend code</span>
              </button>
            </p>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'

const otpDigits = ref(['', '', '', '', '', ''])
const error = ref('')
const success = ref('')
const loading = ref(false)
const resendLoading = ref(false)
const resendCooldown = ref(0)
const userEmail = ref('')

onMounted(() => {
  // Get user data from localStorage
  const userData = localStorage.getItem('user')
  if (userData) {
    const user = JSON.parse(userData)
    userEmail.value = user.email
  }
  
  // Focus first input
  nextTick(() => {
    const firstInput = document.querySelector('input')
    if (firstInput) firstInput.focus()
  })
})

const handleOtpInput = (index) => {
  const value = otpDigits.value[index]
  
  // Only allow digits
  if (value && !/^\d$/.test(value)) {
    otpDigits.value[index] = ''
    return
  }
  
  // Move to next input if value entered
  if (value && index < 5) {
    const nextInput = document.querySelector(`input:nth-of-type(${index + 2})`)
    if (nextInput) nextInput.focus()
  }
  
  // Clear error when user types
  error.value = ''
}

const handleBackspace = (index) => {
  if (!otpDigits.value[index] && index > 0) {
    const prevInput = document.querySelector(`input:nth-of-type(${index})`)
    if (prevInput) prevInput.focus()
  }
}

const handlePaste = (e) => {
  e.preventDefault()
  const pastedData = e.clipboardData.getData('text')
  const digits = pastedData.replace(/\D/g, '').slice(0, 6).split('')
  
  digits.forEach((digit, index) => {
    if (index < 6) {
      otpDigits.value[index] = digit
    }
  })
  
  // Focus last filled input or last input
  const lastIndex = Math.min(digits.length - 1, 5)
  const lastInput = document.querySelector(`input:nth-of-type(${lastIndex + 1})`)
  if (lastInput) lastInput.focus()
}

const handleVerification = async () => {
  error.value = ''
  success.value = ''
  loading.value = true
  
  const otp = otpDigits.value.join('')
  
  if (otp.length !== 6) {
    error.value = 'Please enter all 6 digits'
    loading.value = false
    return
  }
  
  try {
    const response = await fetch('/auth/web/verify-email', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ code: otp }),
      credentials: 'same-origin'
    })
    
    const data = await response.json()
    
    if (data.status === 'success') {
      success.value = 'Email verified successfully! Redirecting...'
      
      // Update user data with the verified user from response
      if (data.user) {
        localStorage.setItem('user', JSON.stringify(data.user))
      }
      
      // Redirect to the appropriate dashboard based on user role
      setTimeout(() => {
        // Use the redirect from server which is based on actual user role
        window.location.href = data.redirect || '/'
      }, 1500)
    } else {
      error.value = data.message || 'Invalid verification code'
      // Clear OTP inputs on error
      otpDigits.value = ['', '', '', '', '', '']
      const firstInput = document.querySelector('input')
      if (firstInput) firstInput.focus()
    }
  } catch (err) {
    error.value = 'An error occurred. Please try again.'
  } finally {
    loading.value = false
  }
}

const resendCode = async () => {
  if (resendCooldown.value > 0) return
  
  error.value = ''
  success.value = ''
  resendLoading.value = true
  
  try {
    const response = await fetch('/auth/web/resend-verification', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      credentials: 'same-origin'
    })
    
    const data = await response.json()
    
    if (data.status === 'success') {
      success.value = 'Verification code sent! Check your email.'
      
      // Start cooldown timer
      resendCooldown.value = 60
      const timer = setInterval(() => {
        resendCooldown.value--
        if (resendCooldown.value <= 0) {
          clearInterval(timer)
        }
      }, 1000)
      
      // Clear OTP inputs
      otpDigits.value = ['', '', '', '', '', '']
      const firstInput = document.querySelector('input')
      if (firstInput) firstInput.focus()
    } else {
      error.value = data.message || 'Failed to resend code'
    }
  } catch (err) {
    error.value = 'An error occurred. Please try again.'
  } finally {
    resendLoading.value = false
  }
}
</script>

<style scoped>
.logo-briski {
  font-family: 'Briski', serif;
}

/* Hide number input spinners */
input[type="text"]::-webkit-outer-spin-button,
input[type="text"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type="text"] {
  -moz-appearance: textfield;
}
</style>