/**
 * SecureStorage Service
 * Provides secure storage for sensitive data like authentication tokens
 * with XSS protection and encryption capabilities
 */

class SecureStorage {
    constructor() {
        this.storagePrefix = 'noxxi_secure_';
        this.tokenKey = 'auth_token';
        this.sessionKey = 'session_data';
        this.encryptionKey = this.getOrCreateEncryptionKey();
    }

    /**
     * Get or create a unique encryption key for this session
     */
    getOrCreateEncryptionKey() {
        // In production, this should be generated server-side
        // For now, we'll use a session-based key
        const key = sessionStorage.getItem('enc_key');
        if (!key) {
            const newKey = this.generateKey();
            sessionStorage.setItem('enc_key', newKey);
            return newKey;
        }
        return key;
    }

    /**
     * Generate a random key for basic obfuscation
     */
    generateKey() {
        const array = new Uint8Array(32);
        crypto.getRandomValues(array);
        return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
    }

    /**
     * Basic XOR encryption for obfuscation (not cryptographically secure)
     * In production, use proper encryption library
     */
    obfuscate(data) {
        if (!data) return '';
        let result = '';
        for (let i = 0; i < data.length; i++) {
            result += String.fromCharCode(
                data.charCodeAt(i) ^ this.encryptionKey.charCodeAt(i % this.encryptionKey.length)
            );
        }
        return btoa(result); // Base64 encode
    }

    /**
     * Deobfuscate data
     */
    deobfuscate(data) {
        if (!data) return '';
        try {
            const decoded = atob(data); // Base64 decode
            let result = '';
            for (let i = 0; i < decoded.length; i++) {
                result += String.fromCharCode(
                    decoded.charCodeAt(i) ^ this.encryptionKey.charCodeAt(i % this.encryptionKey.length)
                );
            }
            return result;
        } catch (e) {
            console.error('Failed to deobfuscate data');
            return null;
        }
    }

    /**
     * Store authentication token securely
     */
    setToken(token) {
        if (!token) return false;
        
        // Sanitize token to prevent XSS
        const sanitizedToken = this.sanitizeInput(token);
        
        // Store obfuscated token
        const obfuscatedToken = this.obfuscate(sanitizedToken);
        
        // Use sessionStorage for better security (cleared on browser close)
        // Also store in localStorage with expiry for persistence
        sessionStorage.setItem(this.storagePrefix + this.tokenKey, obfuscatedToken);
        
        // Store with expiry in localStorage (24 hours)
        const expiryData = {
            value: obfuscatedToken,
            expiry: new Date().getTime() + (24 * 60 * 60 * 1000) // 24 hours
        };
        
        try {
            localStorage.setItem(
                this.storagePrefix + this.tokenKey,
                JSON.stringify(expiryData)
            );
            return true;
        } catch (e) {
            console.error('Storage quota exceeded or localStorage blocked');
            return false;
        }
    }

    /**
     * Retrieve authentication token
     */
    getToken() {
        // Try sessionStorage first (most secure)
        let token = sessionStorage.getItem(this.storagePrefix + this.tokenKey);
        
        if (!token) {
            // Fallback to localStorage with expiry check
            try {
                const stored = localStorage.getItem(this.storagePrefix + this.tokenKey);
                if (stored) {
                    const data = JSON.parse(stored);
                    
                    // Check if token expired
                    if (data.expiry && new Date().getTime() > data.expiry) {
                        this.removeToken();
                        return null;
                    }
                    
                    token = data.value;
                    
                    // Restore to sessionStorage for this session
                    sessionStorage.setItem(this.storagePrefix + this.tokenKey, token);
                }
            } catch (e) {
                console.error('Failed to retrieve token');
                return null;
            }
        }
        
        return token ? this.deobfuscate(token) : null;
    }

    /**
     * Remove authentication token
     */
    removeToken() {
        sessionStorage.removeItem(this.storagePrefix + this.tokenKey);
        localStorage.removeItem(this.storagePrefix + this.tokenKey);
        
        // Also clear legacy keys for backward compatibility
        localStorage.removeItem('auth_token');
        localStorage.removeItem('token');
    }

    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        const token = this.getToken();
        return !!token;
    }

    /**
     * Sanitize input to prevent XSS
     */
    sanitizeInput(input) {
        if (typeof input !== 'string') return input;
        
        // Remove any HTML tags and scripts
        return input
            .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
            .replace(/<[^>]+>/g, '')
            .trim();
    }

    /**
     * Store general data with expiry
     */
    setItem(key, value, expiryMinutes = 60) {
        const sanitizedKey = this.sanitizeInput(key);
        const data = {
            value: this.sanitizeInput(value),
            expiry: new Date().getTime() + (expiryMinutes * 60 * 1000)
        };
        
        try {
            sessionStorage.setItem(
                this.storagePrefix + sanitizedKey,
                JSON.stringify(data)
            );
            return true;
        } catch (e) {
            console.error('Storage error:', e);
            return false;
        }
    }

    /**
     * Retrieve general data
     */
    getItem(key) {
        const sanitizedKey = this.sanitizeInput(key);
        
        try {
            const stored = sessionStorage.getItem(this.storagePrefix + sanitizedKey);
            if (!stored) return null;
            
            const data = JSON.parse(stored);
            
            // Check expiry
            if (data.expiry && new Date().getTime() > data.expiry) {
                this.removeItem(key);
                return null;
            }
            
            return data.value;
        } catch (e) {
            console.error('Failed to retrieve item');
            return null;
        }
    }

    /**
     * Remove general data
     */
    removeItem(key) {
        const sanitizedKey = this.sanitizeInput(key);
        sessionStorage.removeItem(this.storagePrefix + sanitizedKey);
    }

    /**
     * Clear all secure storage
     */
    clear() {
        // Clear all items with our prefix
        const keysToRemove = [];
        
        // Check sessionStorage
        for (let i = 0; i < sessionStorage.length; i++) {
            const key = sessionStorage.key(i);
            if (key && key.startsWith(this.storagePrefix)) {
                keysToRemove.push(key);
            }
        }
        keysToRemove.forEach(key => sessionStorage.removeItem(key));
        
        // Check localStorage
        keysToRemove.length = 0;
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && key.startsWith(this.storagePrefix)) {
                keysToRemove.push(key);
            }
        }
        keysToRemove.forEach(key => localStorage.removeItem(key));
        
        // Clear legacy keys
        localStorage.removeItem('auth_token');
        localStorage.removeItem('token');
    }

    /**
     * Get authorization header for API requests
     */
    getAuthHeader() {
        const token = this.getToken();
        return token ? { 'Authorization': `Bearer ${token}` } : {};
    }
}

// Export singleton instance
const secureStorage = new SecureStorage();

// Prevent tampering in browser console
if (typeof window !== 'undefined') {
    Object.freeze(secureStorage);
    
    // Clear sensitive data on page unload if configured
    window.addEventListener('beforeunload', () => {
        // Optional: Clear session data on navigation
        // secureStorage.clear();
    });
}

export default secureStorage;