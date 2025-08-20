<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 flex">
    <!-- Left Panel - Branding -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-700 relative overflow-hidden">
      <div class="absolute inset-0 bg-black/10"></div>
      <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
      
      <!-- Decorative elements -->
      <div class="absolute top-20 left-20 w-32 h-32 bg-white/10 rounded-full blur-xl"></div>
      <div class="absolute bottom-40 right-20 w-48 h-48 bg-white/5 rounded-full blur-2xl"></div>
      <div class="absolute top-1/2 left-1/3 w-24 h-24 bg-white/10 rounded-full blur-lg"></div>
      
      <div class="relative z-10 flex flex-col justify-between p-12 text-white w-full">
        <div>
          <div class="flex items-center space-x-3 mb-8">
            <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
              </svg>
            </div>
            <h1 class="text-2xl font-bold">{{ appName }}</h1>
          </div>
        </div>
        
        <div class="space-y-6">
          <h2 class="text-4xl font-bold leading-tight">
            Welcome back to the future of events
          </h2>
          <p class="text-xl text-white/80 leading-relaxed">
            Connect with amazing events, discover new experiences, and create unforgettable memories.
          </p>
          
          <div class="flex items-center space-x-4 text-white/60">
            <div class="flex items-center space-x-2">
              <div class="w-2 h-2 bg-green-400 rounded-full"></div>
              <span class="text-sm">10,000+ Events</span>
            </div>
            <div class="flex items-center space-x-2">
              <div class="w-2 h-2 bg-blue-400 rounded-full"></div>
              <span class="text-sm">500+ Cities</span>
            </div>
            <div class="flex items-center space-x-2">
              <div class="w-2 h-2 bg-purple-400 rounded-full"></div>
              <span class="text-sm">1M+ Users</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Panel - Login Form -->
    <div class="flex-1 flex items-center justify-center p-8">
      <div class="w-full max-w-md">
        <!-- Mobile Logo -->
        <div class="lg:hidden flex items-center justify-center mb-8">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-violet-600 to-indigo-600 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
              </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ appName }}</h1>
          </div>
        </div>

        <div class="text-center mb-8">
          <h2 class="text-3xl font-bold text-gray-900 mb-2">Welcome back</h2>
          <p class="text-gray-600">Sign in to your account to continue</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-6">
          <!-- Email Field -->
          <div class="space-y-2">
            <label for="email" class="block text-sm font-medium text-gray-700">
              Email address
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                </svg>
              </div>
              <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                required
                :class="[
                  'block w-full pl-10 pr-3 py-3 border rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200',
                  emailError ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-violet-500 focus:ring-violet-500'
                ]"
                placeholder="Enter your email"
                @blur="validateEmail"
              />
            </div>
            <p v-if="emailError" class="text-sm text-red-600">{{ emailError }}</p>
          </div>

          <!-- Password Field -->
          <div class="space-y-2">
            <label for="password" class="block text-sm font-medium text-gray-700">
              Password
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                required
                class="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all duration-200"
                placeholder="Enter your password"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <svg v-if="showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                </svg>
                <svg v-else class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Remember Me & Forgot Password -->
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input
                id="remember-me"
                v-model="form.remember"
                type="checkbox"
                class="h-4 w-4 text-violet-600 focus:ring-violet-500 border-gray-300 rounded transition-colors duration-200"
              />
              <label for="remember-me" class="ml-2 block text-sm text-gray-700">
                Remember me
              </label>
            </div>

            <a
              href="/password/reset"
              class="text-sm font-medium text-violet-600 hover:text-violet-500 transition-colors duration-200"
            >
              Forgot password?
            </a>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="loading"
            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-[1.02] active:scale-[0.98]"
          >
            <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span v-if="loading">Signing in...</span>
            <span v-else>Sign in</span>
          </button>

          <!-- Sign Up Link -->
          <div class="text-center">
            <p class="text-sm text-gray-600">
              Don't have an account?
              <a href="/register" class="font-medium text-violet-600 hover:text-violet-500 transition-colors duration-200">
                Create account
              </a>
            </p>
          </div>

          <!-- Error Message -->
          <div v-if="error" class="rounded-xl bg-red-50 border border-red-200 p-4">
            <div class="flex">
              <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div class="ml-3">
                <p class="text-sm text-red-800">{{ error }}</p>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import secureStorage from '../services/SecureStorage';

export default {
  name: 'LoginForm',
  data() {
    return {
      appName: 'NOXXI',
      loading: false,
      error: null,
      emailError: null,
      showPassword: false,
      form: {
        email: '',
        password: '',
        remember: false
      }
    }
  },
  methods: {
    validateEmail() {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (this.form.email && !emailRegex.test(this.form.email)) {
        this.emailError = 'Please enter a valid email address';
      } else {
        this.emailError = null;
      }
    },

    async handleLogin() {
      console.log('Login form submitted');
      this.loading = true;
      this.error = null;
      this.emailError = null;

      // Validate email before submitting
      this.validateEmail();
      if (this.emailError) {
        this.loading = false;
        return;
      }

      console.log('Sending login request with:', this.form);

      try {
        // Use web-based login for session authentication
        const response = await fetch('/auth/web/login', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin', // Important for cookies/session
          body: JSON.stringify(this.form)
        });

        const data = await response.json();
        console.log('Login response:', data);

        if (response.ok && data.status === 'success') {
          // Store token using secure storage
          if (data.data && data.data.token) {
            const stored = secureStorage.setToken(data.data.token);
            console.log('Token stored securely:', stored);
          }
          
          // Redirect based on user role
          const user = data.data.user;
          const redirectPath = this.getRedirectPath(user.role);
          console.log('Redirecting to:', redirectPath);
          
          // Use a small delay to ensure token is stored
          setTimeout(() => {
            window.location.href = redirectPath;
          }, 100);
        } else {
          // Handle different types of errors
          console.error('Login failed:', data);
          if (data.errors) {
            const errorMessages = Object.values(data.errors).flat();
            this.error = errorMessages.join(', ');
          } else {
            this.error = data.message || 'Login failed. Please check your credentials.';
          }
        }
      } catch (err) {
        console.error('Login error:', err);
        this.error = 'Network error. Please check your connection and try again.';
      } finally {
        this.loading = false;
      }
    },

    getRedirectPath(role) {
      switch (role) {
        case 'admin':
          return '/admin';
        case 'organizer':
          return '/organizer/dashboard';
        case 'user':
          return '/my-account';
        default:
          return '/';
      }
    }
  },

  mounted() {
    // Focus on email input when component mounts
    this.$nextTick(() => {
      const emailInput = document.getElementById('email');
      if (emailInput) {
        emailInput.focus();
      }
    });
  }
}
</script>