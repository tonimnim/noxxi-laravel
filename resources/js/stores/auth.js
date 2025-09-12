import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        isAuthenticated: false,
        token: localStorage.getItem('token'),
        loading: false,
        errors: null,
        initialized: false
    }),

    getters: {
        currentUser: (state) => state.user,
        isLoggedIn: (state) => state.isAuthenticated,
        authToken: (state) => state.token,
        isOrganizer: (state) => state.user?.organizer !== null,
        organizerId: (state) => state.user?.organizer?.id
    },

    actions: {
        async login(credentials) {
            this.loading = true
            this.errors = null
            
            try {
                const response = await axios.post('/api/auth/login', credentials)
                const { user, access_token } = response.data.data
                
                this.user = user
                this.token = access_token
                this.isAuthenticated = true
                
                // Store token
                localStorage.setItem('token', access_token)
                axios.defaults.headers.common['Authorization'] = `Bearer ${access_token}`
                
                return response.data
            } catch (error) {
                this.errors = error.response?.data?.message || 'Login failed'
                throw error
            } finally {
                this.loading = false
            }
        },

        async register(userData) {
            this.loading = true
            this.errors = null
            
            try {
                const response = await axios.post('/api/auth/register', userData)
                const { user, access_token } = response.data.data
                
                this.user = user
                this.token = access_token
                this.isAuthenticated = true
                
                localStorage.setItem('token', access_token)
                axios.defaults.headers.common['Authorization'] = `Bearer ${access_token}`
                
                return response.data
            } catch (error) {
                this.errors = error.response?.data?.message || 'Registration failed'
                throw error
            } finally {
                this.loading = false
            }
        },

        async logout() {
            try {
                // Try API logout first
                await axios.post('/api/auth/logout')
            } catch (error) {
                // Try web logout if API fails
                try {
                    await fetch('/auth/web/logout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        credentials: 'same-origin'
                    })
                } catch (webError) {
                    console.error('Both API and web logout failed:', { apiError: error, webError })
                }
            } finally {
                // Clear all auth state
                this.clearAuthState()
                
                // Force complete page reload to ensure clean state
                window.location.replace('/')
            }
        },

        clearAuthState() {
            // Reset all state properties to initial values
            this.user = null
            this.token = null
            this.isAuthenticated = false
            this.loading = false
            this.errors = null
            this.initialized = false
            
            // Clear localStorage
            localStorage.removeItem('token')
            
            // Clear axios headers
            delete axios.defaults.headers.common['Authorization']
            
            // Clear other user-specific stores
            this.clearUserStores()
        },

        clearUserStores() {
            try {
                // Import and clear booking store
                import('./booking').then(({ useBookingStore }) => {
                    const bookingStore = useBookingStore()
                    bookingStore.$reset()
                }).catch(() => {
                    // Booking store might not be loaded
                })
                
                // Event store doesn't need clearing as it's not user-specific
                // but we can clear current selections
                import('./event').then(({ useEventStore }) => {
                    const eventStore = useEventStore()
                    // Only clear user-specific filters, keep general data
                    eventStore.$patch({
                        filters: {
                            category: null,
                            city: null,
                            dateFrom: null,
                            dateTo: null,
                            priceMin: null,
                            priceMax: null,
                            search: ''
                        },
                        currentEvent: null
                    })
                }).catch(() => {
                    // Event store might not be loaded
                })
            } catch (error) {
                console.warn('Error clearing user stores:', error)
            }
        },

        async fetchUser() {
            if (!this.token) return
            
            try {
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
                const response = await axios.get('/api/auth/user')
                this.user = response.data.data
                this.isAuthenticated = true
            } catch (error) {
                this.logout()
            }
        },

        setUser(user) {
            this.user = user
            this.isAuthenticated = !!user
        },

        async initializeAuth() {
            if (this.initialized) return
            
            this.loading = true
            
            try {
                // First, ensure we start with a clean slate
                this.user = null
                this.isAuthenticated = false
                
                // Check if we have a token stored
                if (this.token) {
                    // Try to fetch user with the stored token
                    axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
                    const response = await axios.get('/api/auth/user')
                    this.user = response.data.data
                    this.isAuthenticated = true
                } else {
                    // Try web session authentication
                    const response = await fetch('/auth/web/check', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'include'
                    })
                    
                    if (response.ok) {
                        const data = await response.json()
                        if (data.authenticated && data.user) {
                            this.user = data.user
                            this.isAuthenticated = true
                        } else {
                            // Explicitly set as not authenticated
                            this.user = null
                            this.isAuthenticated = false
                        }
                    } else {
                        // Response not ok - not authenticated
                        this.user = null
                        this.isAuthenticated = false
                    }
                }
            } catch (error) {
                console.error('Auth initialization error:', error)
                // If token is invalid, clear it
                if (this.token) {
                    localStorage.removeItem('token')
                    delete axios.defaults.headers.common['Authorization']
                    this.token = null
                }
                // Ensure we're marked as not authenticated
                this.user = null
                this.isAuthenticated = false
            } finally {
                this.loading = false
                this.initialized = true
            }
        }
    }
})