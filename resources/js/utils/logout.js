import { useAuthStore } from '../stores/auth'

/**
 * Complete logout function that clears all session data
 * Uses Pinia auth store for consistency
 */
export async function performLogout() {
    try {
        // Get auth store instance
        let authStore = null
        try {
            authStore = useAuthStore()
        } catch (error) {
            // Store might not be available in all contexts
            console.warn('Auth store not available:', error)
        }

        // Call web logout endpoint (for session-based auth)
        const response = await fetch('/auth/web/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            credentials: 'same-origin'
        })

        // Clear Pinia auth state if available
        if (authStore) {
            authStore.clearAuthState()
        }

        // Clear all browser storage
        localStorage.clear()
        sessionStorage.clear()
        
        // Clear any cookies (though Laravel handles this server-side)
        document.cookie.split(";").forEach(function(c) { 
            document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/")
        })

        // Clear scanner database if available
        try {
            const { default: scannerDB } = await import('../utils/scanner-db')
            await scannerDB.clearAll()
        } catch (error) {
            // Scanner DB might not be available
            console.log('Scanner DB not available for cleanup')
        }

        // Force complete reload to ensure completely clean state
        window.location.replace('/')
        
    } catch (error) {
        console.error('Logout error:', error)
        
        // Even if logout fails, clear all local data and reload
        localStorage.clear()
        sessionStorage.clear()
        window.location.replace('/')
    }
}

// Add global logout handler
window.logout = performLogout;