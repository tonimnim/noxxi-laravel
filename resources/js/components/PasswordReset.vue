<template>
    <div class="min-h-screen bg-gradient-to-br from-green-50 via-blue-50 to-purple-50 flex items-center justify-center p-4">
        <!-- Gradient Background Overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-cyan-100/30 via-pink-100/30 to-yellow-100/30"></div>
        
        <!-- Card Container -->
        <div class="relative w-full max-w-md">
            <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-8 md:p-10">
                <!-- Gradient Background Inside Card -->
                <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-blue-50/50 via-transparent to-pink-50/50"></div>
                
                <!-- Content -->
                <div class="relative z-10">
                    <!-- Logo -->
                    <div class="flex justify-center mb-6">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                            </div>
                            <span class="text-xl font-bold text-gray-900">NOXXI</span>
                        </div>
                    </div>
                    
                    <!-- Title -->
                    <h1 class="text-2xl font-bold text-center text-gray-900 mb-2">
                        Reset your password
                    </h1>
                    
                    <!-- Subtitle -->
                    <p class="text-center text-gray-600 text-sm mb-8">
                        {{ step === 'request' ? 'Enter your email address and we\'ll send you password reset instructions.' : 'Enter the code we sent to your email and create a new password.' }}
                    </p>
                    
                    <!-- Step 1: Request Reset -->
                    <div v-if="step === 'request'">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address *
                            </label>
                            <input
                                v-model="email"
                                type="email"
                                placeholder="Input your email"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                :class="{ 'border-red-500': emailError }"
                                @input="emailError = ''"
                            >
                            <p v-if="emailError" class="mt-2 text-sm text-red-600">
                                {{ emailError }}
                            </p>
                        </div>
                        
                        <!-- Request Reset Button -->
                        <button
                            @click="requestReset"
                            :disabled="!email || requestLoading"
                            class="w-full py-3 px-4 bg-gray-900 text-white font-medium rounded-xl hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="requestLoading" class="flex items-center justify-center">
                                <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Sending...
                            </span>
                            <span v-else>Send Reset Instructions</span>
                        </button>
                    </div>
                    
                    <!-- Step 2: Verify Code & Set New Password -->
                    <div v-if="step === 'verify'">
                        <!-- Verification Code -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Verification Code *
                            </label>
                            <div class="flex gap-2 justify-center mb-4">
                                <input
                                    v-for="(digit, index) in resetCode"
                                    :key="index"
                                    :ref="`code${index}`"
                                    v-model="resetCode[index]"
                                    @input="handleCodeInput(index, $event)"
                                    @keydown="handleCodeKeydown(index, $event)"
                                    @paste="handleCodePaste($event)"
                                    type="text"
                                    maxlength="1"
                                    class="w-12 h-12 text-center text-lg font-semibold border-2 rounded-xl transition-all duration-200"
                                    :class="[
                                        resetCode[index] ? 'border-blue-500 bg-blue-50/50' : 'border-gray-300',
                                        'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent'
                                    ]"
                                >
                            </div>
                        </div>
                        
                        <!-- New Password -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                New Password *
                            </label>
                            <div class="relative">
                                <input
                                    v-model="newPassword"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Enter new password"
                                    class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                    :class="{ 'border-red-500': passwordError }"
                                    @input="passwordError = ''"
                                >
                                <button
                                    @click="showPassword = !showPassword"
                                    type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                                >
                                    <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Confirm Password *
                            </label>
                            <div class="relative">
                                <input
                                    v-model="confirmPassword"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    placeholder="Confirm new password"
                                    class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                    :class="{ 'border-red-500': confirmError }"
                                    @input="confirmError = ''"
                                >
                                <button
                                    @click="showConfirmPassword = !showConfirmPassword"
                                    type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                                >
                                    <svg v-if="!showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                    </svg>
                                </button>
                            </div>
                            <p v-if="confirmError" class="mt-2 text-sm text-red-600">
                                {{ confirmError }}
                            </p>
                        </div>
                        
                        <!-- Reset Password Button -->
                        <button
                            @click="resetPassword"
                            :disabled="!isResetFormValid || resetLoading"
                            class="w-full py-3 px-4 bg-gray-900 text-white font-medium rounded-xl hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="resetLoading" class="flex items-center justify-center">
                                <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Resetting...
                            </span>
                            <span v-else>Reset Password</span>
                        </button>
                    </div>
                    
                    <!-- Alternative Actions -->
                    <div class="mt-8 flex flex-col gap-3">
                        <a 
                            href="/login"
                            class="w-full py-3 px-4 bg-white border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 text-center"
                        >
                            Back to Login
                        </a>
                        
                        <a 
                            href="/register"
                            class="w-full py-3 px-4 bg-white border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 text-center"
                        >
                            Sign Up
                        </a>
                    </div>
                    
                    <!-- Footer -->
                    <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                        <p class="text-xs text-gray-500">
                            © 2024 NOXXI. All rights reserved.
                            <a href="#" class="ml-2 text-gray-700 hover:text-gray-900">Terms & Conditions</a>
                            <a href="#" class="ml-2 text-gray-700 hover:text-gray-900">Privacy Policy</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'PasswordReset',
    data() {
        return {
            step: 'request', // 'request' or 'verify'
            email: '',
            emailError: '',
            resetCode: ['', '', '', '', '', ''],
            newPassword: '',
            confirmPassword: '',
            passwordError: '',
            confirmError: '',
            showPassword: false,
            showConfirmPassword: false,
            requestLoading: false,
            resetLoading: false
        };
    },
    computed: {
        isCodeComplete() {
            return this.resetCode.every(digit => digit !== '');
        },
        isResetFormValid() {
            return this.isCodeComplete && this.newPassword && this.confirmPassword;
        },
        verificationCode() {
            return this.resetCode.join('');
        }
    },
    methods: {
        async requestReset() {
            if (!this.email) {
                this.emailError = 'Email is required';
                return;
            }
            
            if (!this.validateEmail(this.email)) {
                this.emailError = 'Please enter a valid email address';
                return;
            }
            
            this.requestLoading = true;
            this.emailError = '';
            
            try {
                const response = await fetch('/api/auth/password/request-reset', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ email: this.email })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    this.step = 'verify';
                    this.$nextTick(() => {
                        this.$refs.code0[0].focus();
                    });
                } else {
                    this.emailError = data.message || 'Failed to send reset instructions';
                }
            } catch (error) {
                this.emailError = 'An error occurred. Please try again.';
            } finally {
                this.requestLoading = false;
            }
        },
        
        async resetPassword() {
            // Validate passwords
            if (!this.newPassword || this.newPassword.length < 8) {
                this.passwordError = 'Password must be at least 8 characters';
                return;
            }
            
            if (this.newPassword !== this.confirmPassword) {
                this.confirmError = 'Passwords do not match';
                return;
            }
            
            this.resetLoading = true;
            this.passwordError = '';
            this.confirmError = '';
            
            try {
                const response = await fetch('/api/auth/password/reset', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        email: this.email,
                        code: this.verificationCode,
                        password: this.newPassword,
                        password_confirmation: this.confirmPassword
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    // Redirect to login with success message
                    window.location.href = '/login?reset=success';
                } else {
                    this.confirmError = data.message || 'Failed to reset password';
                }
            } catch (error) {
                this.confirmError = 'An error occurred. Please try again.';
            } finally {
                this.resetLoading = false;
            }
        },
        
        handleCodeInput(index, event) {
            const value = event.target.value;
            
            // Only allow digits
            if (value && !/^\d$/.test(value)) {
                this.resetCode[index] = '';
                return;
            }
            
            // Move to next input if value entered
            if (value && index < 5) {
                this.$nextTick(() => {
                    this.$refs[`code${index + 1}`][0].focus();
                });
            }
        },
        
        handleCodeKeydown(index, event) {
            // Handle backspace
            if (event.key === 'Backspace' && !this.resetCode[index] && index > 0) {
                event.preventDefault();
                this.$refs[`code${index - 1}`][0].focus();
            }
            
            // Handle arrow keys
            if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault();
                this.$refs[`code${index - 1}`][0].focus();
            }
            if (event.key === 'ArrowRight' && index < 5) {
                event.preventDefault();
                this.$refs[`code${index + 1}`][0].focus();
            }
        },
        
        handleCodePaste(event) {
            event.preventDefault();
            const pastedData = event.clipboardData.getData('text');
            const digits = pastedData.replace(/\D/g, '').slice(0, 6).split('');
            
            digits.forEach((digit, index) => {
                if (index < 6) {
                    this.resetCode[index] = digit;
                }
            });
            
            // Focus last filled input or last input
            const lastIndex = Math.min(digits.length - 1, 5);
            this.$refs[`code${lastIndex}`][0].focus();
        },
        
        validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    }
};
</script>

<style scoped>
/* Remove spinner from number inputs */
input[type="text"]::-webkit-inner-spin-button,
input[type="text"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

input[type="text"] {
    -moz-appearance: textfield;
}
</style>