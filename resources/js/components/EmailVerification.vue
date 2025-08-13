<template>
    <div class="min-h-screen bg-gradient-to-br from-green-50 via-blue-50 to-purple-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gradient-to-br from-cyan-100/30 via-pink-100/30 to-yellow-100/30"></div>
        
        <div class="relative w-full max-w-md">
            <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-8 md:p-10">
                <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-blue-50/50 via-transparent to-pink-50/50"></div>
                
                <div class="relative z-10">
                    <!-- Logo -->
                    <div class="flex justify-center mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-blue-500 rounded-2xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Title -->
                    <h1 class="text-2xl font-bold text-center text-gray-900 mb-2">Verify your email</h1>
                    <p class="text-center text-gray-600 text-sm mb-8">
                        Enter the verification code we sent to<br>
                        <span class="font-medium text-gray-900">{{ userEmail }}</span>
                    </p>
                    
                    <!-- Code Input -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Verification Code</label>
                        <div class="flex gap-2 justify-center">
                            <input
                                v-for="(digit, i) in 6"
                                :key="i"
                                :ref="`digit${i}`"
                                v-model="code[i]"
                                @input="onInput(i)"
                                @keydown.backspace="onBackspace(i)"
                                @paste="onPaste"
                                maxlength="1"
                                type="text"
                                class="w-12 h-12 text-center text-lg font-semibold border-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                :class="code[i] ? 'border-blue-500 bg-blue-50/50' : 'border-gray-300'"
                            >
                        </div>
                        <p v-if="error" class="mt-2 text-sm text-red-600 text-center">{{ error }}</p>
                    </div>
                    
                    <!-- Buttons -->
                    <button @click="verify" :disabled="!isComplete || loading"
                        class="w-full py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-medium rounded-xl hover:from-blue-700 hover:to-blue-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ loading ? 'Verifying...' : 'Verify Email' }}
                    </button>
                    
                    <div class="mt-6 text-center">
                        <button @click="resend" :disabled="cooldown > 0"
                            class="text-sm text-blue-600 hover:text-blue-500 disabled:text-gray-400">
                            {{ cooldown > 0 ? `Resend in ${cooldown}s` : 'Resend code' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            code: ['', '', '', '', '', ''],
            error: '',
            loading: false,
            cooldown: 0,
            userEmail: ''
        };
    },
    computed: {
        isComplete() {
            return this.code.every(d => d !== '');
        },
        verificationCode() {
            return this.code.join('');
        }
    },
    mounted() {
        this.fetchUser();
        this.$refs.digit0[0].focus();
    },
    methods: {
        async fetchUser() {
            try {
                const res = await fetch('/api/auth/user', {
                    headers: { 
                        'Authorization': 'Bearer ' + localStorage.getItem('token'),
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                this.userEmail = data.data.user.email;
            } catch (e) {
                console.error(e);
            }
        },
        
        onInput(index) {
            if (this.code[index] && index < 5) {
                this.$refs[`digit${index + 1}`][0].focus();
            }
            if (this.isComplete) {
                this.verify();
            }
        },
        
        onBackspace(index) {
            if (!this.code[index] && index > 0) {
                this.$refs[`digit${index - 1}`][0].focus();
            }
        },
        
        onPaste(e) {
            e.preventDefault();
            const text = e.clipboardData.getData('text').slice(0, 6);
            text.split('').forEach((char, i) => {
                if (i < 6) this.code[i] = char;
            });
        },
        
        async verify() {
            if (!this.isComplete) return;
            
            this.loading = true;
            this.error = '';
            
            try {
                const res = await fetch('/api/auth/verify-email', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + localStorage.getItem('token'),
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ code: this.verificationCode })
                });
                
                const data = await res.json();
                
                if (res.ok) {
                    window.location.href = data.data.redirect;
                } else {
                    this.error = data.message || 'Invalid code';
                    this.code = ['', '', '', '', '', ''];
                    this.$refs.digit0[0].focus();
                }
            } catch (e) {
                this.error = 'An error occurred';
            } finally {
                this.loading = false;
            }
        },
        
        async resend() {
            if (this.cooldown > 0) return;
            
            try {
                await fetch('/api/auth/resend-verification', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem('token'),
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                this.cooldown = 60;
                const timer = setInterval(() => {
                    this.cooldown--;
                    if (this.cooldown <= 0) clearInterval(timer);
                }, 1000);
                
                this.code = ['', '', '', '', '', ''];
                this.$refs.digit0[0].focus();
            } catch (e) {
                console.error(e);
            }
        }
    }
};
</script>