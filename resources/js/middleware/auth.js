import { useAuth } from '../composables/useAuth'

export const authMiddleware = {
    async checkAuth(to, from, next) {
        const auth = useAuth()
        const result = await auth.checkAuth()

        const publicPaths = ['/', '/login', '/register', '/login/organizer', '/register/organizer', '/about', '/contact', '/terms', '/privacy']
        const isPublicPath = publicPaths.includes(to.path)

        if (!result.authenticated && !isPublicPath) {
            // User is not authenticated and trying to access protected route
            if (to.path.startsWith('/organizer')) {
                return '/login/organizer'
            } else if (to.path.startsWith('/admin')) {
                return '/login'  
            } else {
                return '/login'
            }
        }

        if (result.authenticated) {
            // Check role-based access
            const user = auth.user.value
            
            if (to.path.startsWith('/admin') && user.role !== 'admin') {
                return '/'
            }
            
            if (to.path.startsWith('/organizer') && user.role !== 'organizer') {
                return '/'
            }
            
            if (to.path.startsWith('/account') && user.role !== 'user') {
                return '/'
            }

            // Redirect authenticated users away from login pages
            if (isPublicPath && ['/login', '/register', '/login/organizer', '/register/organizer'].includes(to.path)) {
                return result.redirect
            }
        }

        return null // Allow navigation
    }
}