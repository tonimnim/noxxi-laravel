/**
 * Complete logout function that clears all session data
 */
export async function performLogout() {
    try {
        // Call logout endpoint
        const response = await fetch('/auth/web/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            credentials: 'same-origin'
        });

        // Clear all localStorage items
        localStorage.clear();
        
        // Clear sessionStorage too
        sessionStorage.clear();
        
        // Remove any cookies we might have set (though Laravel handles this server-side)
        document.cookie.split(";").forEach(function(c) { 
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
        });

        // Always redirect to home page after logout
        window.location.href = '/';
        
    } catch (error) {
        console.error('Logout error:', error);
        // Even if logout fails, clear local data and redirect
        localStorage.clear();
        sessionStorage.clear();
        window.location.href = '/';
    }
}

// Add global logout handler
window.logout = performLogout;