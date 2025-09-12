/**
 * Cache utility for managing sessionStorage caching across the application
 */

export const CacheManager = {
  /**
   * Default cache duration (10 minutes)
   */
  DEFAULT_DURATION: 10 * 60 * 1000,

  /**
   * Get cached data if valid
   * @param {string} key - Cache key
   * @param {number} duration - Cache duration in milliseconds
   * @returns {any|null} - Cached data or null if invalid/expired
   */
  get(key, duration = this.DEFAULT_DURATION) {
    try {
      const cached = sessionStorage.getItem(key)
      const cacheTime = sessionStorage.getItem(`${key}_time`)
      const now = Date.now()
      
      if (cached && cacheTime && (now - parseInt(cacheTime)) < duration) {
        return JSON.parse(cached)
      }
    } catch (error) {
      console.error('Cache read error:', error)
      this.clear(key)
    }
    return null
  },

  /**
   * Save data to cache
   * @param {string} key - Cache key
   * @param {any} data - Data to cache
   */
  set(key, data) {
    try {
      sessionStorage.setItem(key, JSON.stringify(data))
      sessionStorage.setItem(`${key}_time`, Date.now().toString())
    } catch (error) {
      console.error('Cache write error:', error)
      // If quota exceeded, clear old caches
      if (error.name === 'QuotaExceededError') {
        this.clearOldCaches()
        // Try again
        try {
          sessionStorage.setItem(key, JSON.stringify(data))
          sessionStorage.setItem(`${key}_time`, Date.now().toString())
        } catch (retryError) {
          console.error('Cache write failed after clearing:', retryError)
        }
      }
    }
  },

  /**
   * Clear specific cache
   * @param {string} key - Cache key to clear
   */
  clear(key) {
    sessionStorage.removeItem(key)
    sessionStorage.removeItem(`${key}_time`)
  },

  /**
   * Clear all caches matching a pattern
   * @param {string} pattern - Pattern to match cache keys
   */
  clearPattern(pattern) {
    const keys = Object.keys(sessionStorage)
    keys.forEach(key => {
      if (key.includes(pattern)) {
        sessionStorage.removeItem(key)
      }
    })
  },

  /**
   * Clear old/expired caches to free up space
   */
  clearOldCaches() {
    const now = Date.now()
    const keys = Object.keys(sessionStorage)
    
    keys.forEach(key => {
      if (key.endsWith('_time')) {
        const time = parseInt(sessionStorage.getItem(key))
        if (time && (now - time) > this.DEFAULT_DURATION) {
          const dataKey = key.replace('_time', '')
          this.clear(dataKey)
        }
      }
    })
  },

  /**
   * Clear all app caches
   */
  clearAll() {
    const patterns = [
      'events_section',
      'sports_section',
      'cinema_section',
      'experiences_section',
      'featured_events',
      'listing_page'
    ]
    
    patterns.forEach(pattern => {
      this.clearPattern(pattern)
    })
  },

  /**
   * Get cache size in bytes
   * @returns {number} - Total size of cached data
   */
  getSize() {
    let size = 0
    for (let key in sessionStorage) {
      if (sessionStorage.hasOwnProperty(key)) {
        size += sessionStorage[key].length + key.length
      }
    }
    return size
  },

  /**
   * Check if cache is available
   * @returns {boolean} - True if sessionStorage is available
   */
  isAvailable() {
    try {
      const test = '__cache_test__'
      sessionStorage.setItem(test, 'test')
      sessionStorage.removeItem(test)
      return true
    } catch (e) {
      return false
    }
  }
}