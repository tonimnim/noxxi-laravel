<script>
(function() {
    // Check if user is authenticated and redirect if necessary
    const checkAuthAndRedirect = async () => {
        const currentPath = window.location.pathname;
        
        // Define public and protected paths
        const publicPaths = ['/', '/login', '/register', '/login/organizer', '/register/organizer', '/about', '/contact', '/terms', '/privacy'];
        const protectedPaths = {
            '/admin': 'admin',
            '/organizer/dashboard': 'organizer',
            '/organizer': 'organizer',
            '/account': 'user',
        };
        
        // Check if current path is protected
        const isProtectedPath = Object.keys(protectedPaths).some(path => currentPath.startsWith(path));
        const isAuthPage = ['/login', '/register', '/login/organizer', '/register/organizer'].includes(currentPath);
        
        try {
            const response = await fetch('/auth/web/check', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            const data = await response.json();
                
                if (data.authenticated) {
                    // User is authenticated
                    const user = data.user;
                    const redirectPath = data.redirect;
                    
                    // Check if email is verified (admins don't need verification)
                    if (!user.email_verified_at && user.role !== 'admin' && currentPath !== '/email/verify') {
                        // Redirect unverified users to email verification
                        window.location.href = '/email/verify';
                        return;
                    }
                    
                    // If on auth page, redirect to dashboard
                    if (isAuthPage) {
                        window.location.href = redirectPath;
                        return;
                    }
                    
                    // Check role-based access for protected paths
                    if (isProtectedPath) {
                        const requiredRole = Object.entries(protectedPaths).find(([path]) => 
                            currentPath.startsWith(path)
                        )?.[1];
                        
                        if (requiredRole && user.role !== requiredRole) {
                            // User doesn't have the right role for this path
                            window.location.href = redirectPath;
                        }
                    }
                } else {
                    // User is not authenticated
                    // If on protected path, redirect to login
                    if (isProtectedPath) {
                        if (currentPath.startsWith('/organizer')) {
                            window.location.href = '/login/organizer';
                        } else {
                            window.location.href = '/login';
                        }
                    }
                }
            } catch (error) {
                console.error('Auth check failed:', error);
                
                // If on protected path, redirect to login
                if (isProtectedPath) {
                    if (currentPath.startsWith('/organizer')) {
                        window.location.href = '/login/organizer';
                    } else {
                        window.location.href = '/login';
                    }
                }
            }
    };
    
    // Run auth check when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkAuthAndRedirect);
    } else {
        checkAuthAndRedirect();
    }
})();
</script>