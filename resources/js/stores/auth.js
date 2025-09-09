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
                await axios.post('/api/auth/logout')
            } catch (error) {
                console.error('Logout error:', error)
            } finally {
                this.user = null
                this.token = null
                this.isAuthenticated = false
                localStorage.removeItem('token')
                delete axios.defaults.headers.common['Authorization']
                window.location.href = '/'
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
                        }
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
                this.isAuthenticated = false
            } finally {
                this.loading = false
                this.initialized = true
            }
        }
    }
})