import { defineStore } from 'pinia'
import axios from 'axios'

export const useEventStore = defineStore('event', {
    state: () => ({
        events: [],
        featuredEvents: [],
        currentEvent: null,
        categories: [],
        filters: {
            category: null,
            city: null,
            dateFrom: null,
            dateTo: null,
            priceMin: null,
            priceMax: null,
            search: ''
        },
        pagination: {
            currentPage: 1,
            lastPage: 1,
            perPage: 12,
            total: 0
        },
        loading: false,
        errors: null
    }),

    getters: {
        filteredEvents: (state) => {
            let filtered = [...state.events]
            
            if (state.filters.category) {
                filtered = filtered.filter(e => e.category_id === state.filters.category)
            }
            
            if (state.filters.city) {
                filtered = filtered.filter(e => e.city === state.filters.city)
            }
            
            if (state.filters.search) {
                const search = state.filters.search.toLowerCase()
                filtered = filtered.filter(e => 
                    e.title.toLowerCase().includes(search) ||
                    e.description?.toLowerCase().includes(search)
                )
            }
            
            return filtered
        },
        
        eventsByCategory: (state) => (categorySlug) => {
            return state.events.filter(e => e.category?.slug === categorySlug)
        },
        
        upcomingEvents: (state) => {
            const now = new Date()
            return state.events.filter(e => new Date(e.event_date) > now)
        }
    },

    actions: {
        async fetchEvents(params = {}) {
            this.loading = true
            this.errors = null
            
            try {
                const queryParams = {
                    page: this.pagination.currentPage,
                    per_page: this.pagination.perPage,
                    ...this.filters,
                    ...params
                }
                
                const response = await axios.get('/api/events', { params: queryParams })
                const { events, meta } = response.data.data
                
                this.events = events
                this.pagination = {
                    currentPage: meta.current_page,
                    lastPage: meta.last_page,
                    perPage: meta.per_page,
                    total: meta.total
                }
                
                return response.data
            } catch (error) {
                this.errors = error.response?.data?.message || 'Failed to fetch events'
                throw error
            } finally {
                this.loading = false
            }
        },

        async fetchFeaturedEvents() {
            try {
                const response = await axios.get('/api/events/featured')
                this.featuredEvents = response.data.data.events || []
                return response.data
            } catch (error) {
                console.error('Failed to fetch featured events:', error)
            }
        },

        async fetchEvent(id) {
            this.loading = true
            this.errors = null
            
            try {
                const response = await axios.get(`/api/events/${id}`)
                this.currentEvent = response.data.data
                return response.data
            } catch (error) {
                this.errors = error.response?.data?.message || 'Failed to fetch event'
                throw error
            } finally {
                this.loading = false
            }
        },

        async fetchCategories() {
            try {
                const response = await axios.get('/api/categories')
                this.categories = response.data.data
                return response.data
            } catch (error) {
                console.error('Failed to fetch categories:', error)
            }
        },

        setFilter(filterName, value) {
            this.filters[filterName] = value
            this.pagination.currentPage = 1 // Reset to first page when filtering
            this.fetchEvents()
        },

        clearFilters() {
            this.filters = {
                category: null,
                city: null,
                dateFrom: null,
                dateTo: null,
                priceMin: null,
                priceMax: null,
                search: ''
            }
            this.pagination.currentPage = 1
            this.fetchEvents()
        },

        setPage(page) {
            this.pagination.currentPage = page
            this.fetchEvents()
        },

        setCurrentEvent(event) {
            this.currentEvent = event
        }
    }
})