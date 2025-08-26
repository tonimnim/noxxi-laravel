import { ref, computed } from 'vue'
import axios from 'axios'

const user = ref(null)
const isAuthenticated = ref(false)
const isLoading = ref(true)

export function useAuth() {
    // Initialize axios defaults
    const token = localStorage.getItem('token')
    if (token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    }

    // Check if user is authenticated
    const checkAuth = async () => {
        isLoading.value = true
        const token = localStorage.getItem('token')
        
        if (!token) {
            isAuthenticated.value = false
            user.value = null
            isLoading.value = false
            return { authenticated: false, redirect: null }
        }

        try {
            const response = await axios.get('/api/auth/check', {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            })

            if (response.data.data.authenticated) {
                user.value = response.data.data.user
                isAuthenticated.value = true
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
                
                return {
                    authenticated: true,
                    redirect: response.data.data.redirect
                }
            } else {
                // Token is invalid, clear it
                localStorage.removeItem('token')
                localStorage.removeItem('user')
                delete axios.defaults.headers.common['Authorization']
                isAuthenticated.value = false
                user.value = null
            }
        } catch (error) {
            console.error('Auth check failed:', error)
            // Clear invalid token
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            delete axios.defaults.headers.common['Authorization']
            isAuthenticated.value = false
            user.value = null
        } finally {
            isLoading.value = false
        }

        return { authenticated: false, redirect: null }
    }

    // Auto-redirect based on role
    const autoRedirect = async () => {
        const result = await checkAuth()
        
        if (result.authenticated && result.redirect) {
            // Check if we're not already on the correct page
            const currentPath = window.location.pathname
            
            // Don't redirect if already on the dashboard
            if (!currentPath.startsWith(result.redirect)) {
                window.location.href = result.redirect
            }
        }
        
        return result
    }

    // Login function
    const login = async (credentials) => {
        try {
            const response = await axios.post('/api/auth/login', credentials)
            
            if (response.data.status === 'success') {
                const { user: userData, token, refresh_token } = response.data.data
                
                // Store tokens
                localStorage.setItem('token', token)
                if (refresh_token) {
                    localStorage.setItem('refresh_token', refresh_token)
                }
                localStorage.setItem('user', JSON.stringify(userData))
                
                // Set axios header
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
                
                // Update state
                user.value = userData
                isAuthenticated.value = true
                
                // Get redirect path based on role
                const redirectPath = getRedirectPath(userData)
                
                return {
                    success: true,
                    redirect: redirectPath
                }
            }
            
            return {
                success: false,
                message: response.data.message || 'Login failed'
            }
        } catch (error) {
            return {
                success: false,
                message: error.response?.data?.message || 'An error occurred during login'
            }
        }
    }

    // Logout function
    const logout = async () => {
        try {
            const token = localStorage.getItem('token')
            if (token) {
                await axios.post('/api/auth/logout', {}, {
                    headers: { 'Authorization': `Bearer ${token}` }
                })
            }
        } catch (error) {
            console.error('Logout error:', error)
        } finally {
            // Clear storage and state
            localStorage.removeItem('token')
            localStorage.removeItem('refresh_token')
            localStorage.removeItem('user')
            delete axios.defaults.headers.common['Authorization']
            
            user.value = null
            isAuthenticated.value = false
            
            // Redirect to home
            window.location.href = '/'
        }
    }

    // Refresh token
    const refreshToken = async () => {
        const refresh = localStorage.getItem('refresh_token')
        
        if (!refresh) {
            return false
        }

        try {
            const response = await axios.post('/api/auth/refresh', {
                refresh_token: refresh
            })

            if (response.data.status === 'success') {
                const { token, refresh_token, expires_at } = response.data.data
                
                // Update tokens
                localStorage.setItem('token', token)
                localStorage.setItem('refresh_token', refresh_token)
                localStorage.setItem('token_expires', expires_at)
                
                // Update axios header
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
                
                return true
            }
        } catch (error) {
            console.error('Token refresh failed:', error)
        }

        return false
    }

    // Helper function to get redirect path
    const getRedirectPath = (userData) => {
        switch (userData.role) {
            case 'admin':
                return '/admin'
            case 'organizer':
                return '/organizer/dashboard'
            case 'user':
                return '/'
            default:
                return '/'
        }
    }

    // Computed properties
    const isOrganizer = computed(() => user.value?.role === 'organizer')
    const isAdmin = computed(() => user.value?.role === 'admin')
    const isUser = computed(() => user.value?.role === 'user')

    return {
        user,
        isAuthenticated,
        isLoading,
        isOrganizer,
        isAdmin,
        isUser,
        checkAuth,
        autoRedirect,
        login,
        logout,
        refreshToken
    }
}