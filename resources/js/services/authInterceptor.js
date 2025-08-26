import axios from 'axios'

let isRefreshing = false
let failedQueue = []

const processQueue = (error, token = null) => {
    failedQueue.forEach(prom => {
        if (error) {
            prom.reject(error)
        } else {
            prom.resolve(token)
        }
    })
    
    failedQueue = []
}

// Request interceptor to add token
axios.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token')
        if (token) {
            config.headers['Authorization'] = `Bearer ${token}`
        }
        return config
    },
    error => {
        return Promise.reject(error)
    }
)

// Response interceptor to handle 401 errors
axios.interceptors.response.use(
    response => response,
    async error => {
        const originalRequest = error.config

        // If error is 401 and we haven't already tried to refresh
        if (error.response?.status === 401 && !originalRequest._retry) {
            if (isRefreshing) {
                // If we're already refreshing, queue this request
                return new Promise((resolve, reject) => {
                    failedQueue.push({ resolve, reject })
                }).then(token => {
                    originalRequest.headers['Authorization'] = `Bearer ${token}`
                    return axios(originalRequest)
                }).catch(err => {
                    return Promise.reject(err)
                })
            }

            originalRequest._retry = true
            isRefreshing = true

            const refreshToken = localStorage.getItem('refresh_token')

            if (!refreshToken) {
                // No refresh token, redirect to login
                localStorage.removeItem('token')
                localStorage.removeItem('refresh_token')
                localStorage.removeItem('user')
                window.location.href = '/login'
                return Promise.reject(error)
            }

            try {
                const response = await axios.post('/api/auth/refresh', {
                    refresh_token: refreshToken
                })

                if (response.data.status === 'success') {
                    const { token, refresh_token } = response.data.data
                    
                    // Update tokens
                    localStorage.setItem('token', token)
                    localStorage.setItem('refresh_token', refresh_token)
                    
                    // Update default header
                    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
                    
                    // Process queued requests
                    processQueue(null, token)
                    
                    // Retry original request
                    originalRequest.headers['Authorization'] = `Bearer ${token}`
                    return axios(originalRequest)
                }
            } catch (refreshError) {
                // Refresh failed, clear tokens and redirect to login
                processQueue(refreshError, null)
                localStorage.removeItem('token')
                localStorage.removeItem('refresh_token')
                localStorage.removeItem('user')
                
                // Redirect based on current path
                const currentPath = window.location.pathname
                if (currentPath.startsWith('/organizer')) {
                    window.location.href = '/login/organizer'
                } else {
                    window.location.href = '/login'
                }
                
                return Promise.reject(refreshError)
            } finally {
                isRefreshing = false
            }
        }

        return Promise.reject(error)
    }
)

export default axios