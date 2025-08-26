import { useAuth } from '../composables/useAuth'
import '../services/authInterceptor'

export default {
    install(app) {
        const auth = useAuth()
        
        // Make auth available globally
        app.config.globalProperties.$auth = auth
        app.provide('auth', auth)
        
        // Check auth on app initialization
        auth.checkAuth().then(result => {
            // Auto-redirect if needed and not on public pages
            const publicPaths = ['/', '/login', '/register', '/login/organizer', '/register/organizer', '/about', '/contact']
            const currentPath = window.location.pathname
            
            if (result.authenticated && result.redirect) {
                // If user is on a public page and authenticated, redirect to dashboard
                if (publicPaths.includes(currentPath)) {
                    window.location.href = result.redirect
                }
            } else if (!result.authenticated) {
                // If user is not authenticated and trying to access protected routes
                const protectedPaths = ['/admin', '/organizer', '/account', '/my-account']
                
                if (protectedPaths.some(path => currentPath.startsWith(path))) {
                    // Redirect to appropriate login page
                    if (currentPath.startsWith('/organizer')) {
                        window.location.href = '/login/organizer'
                    } else if (currentPath.startsWith('/admin')) {
                        window.location.href = '/login'
                    } else {
                        window.location.href = '/login'
                    }
                }
            }
        })
    }
}