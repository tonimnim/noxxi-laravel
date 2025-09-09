import { defineStore } from 'pinia'
import axios from 'axios'

export const useBookingStore = defineStore('booking', {
    state: () => ({
        currentBooking: null,
        selectedTickets: [],
        totalAmount: 0,
        bookingReference: null,
        paymentStatus: null,
        loading: false,
        errors: null,
        currentEvent: null
    }),

    getters: {
        hasSelectedTickets: (state) => state.selectedTickets.length > 0,
        ticketCount: (state) => state.selectedTickets.reduce((sum, ticket) => sum + ticket.quantity, 0),
        formattedTotal: (state) => {
            const currency = state.currentEvent?.currency || 'KES'
            return `${currency} ${state.totalAmount.toLocaleString()}`
        }
    },

    actions: {
        setEvent(event) {
            this.currentEvent = event
        },

        addTicket(ticketType, quantity) {
            const existingTicket = this.selectedTickets.find(t => t.id === ticketType.id)
            
            if (existingTicket) {
                existingTicket.quantity = quantity
            } else {
                this.selectedTickets.push({
                    ...ticketType,
                    quantity
                })
            }
            
            this.calculateTotal()
        },

        removeTicket(ticketTypeId) {
            this.selectedTickets = this.selectedTickets.filter(t => t.id !== ticketTypeId)
            this.calculateTotal()
        },

        calculateTotal() {
            this.totalAmount = this.selectedTickets.reduce((sum, ticket) => {
                return sum + (ticket.price * ticket.quantity)
            }, 0)
        },

        async createBooking(eventId, customerData = {}) {
            this.loading = true
            this.errors = null
            
            try {
                const payload = {
                    event_id: eventId,
                    tickets: this.selectedTickets.map(t => ({
                        ticket_type_id: t.id,
                        quantity: t.quantity,
                        price: t.price
                    })),
                    total_amount: this.totalAmount,
                    ...customerData
                }
                
                const response = await axios.post('/api/bookings', payload)
                this.currentBooking = response.data.data
                this.bookingReference = response.data.data.booking_reference
                
                return response.data
            } catch (error) {
                this.errors = error.response?.data?.message || 'Booking failed'
                throw error
            } finally {
                this.loading = false
            }
        },

        async initializePayment(bookingId, paymentMethod = 'paystack') {
            this.loading = true
            
            try {
                const response = await axios.post(`/api/payments/${paymentMethod}/initialize`, {
                    booking_id: bookingId
                })
                
                return response.data
            } catch (error) {
                this.errors = error.response?.data?.message || 'Payment initialization failed'
                throw error
            } finally {
                this.loading = false
            }
        },

        async verifyPayment(reference) {
            this.loading = true
            
            try {
                const response = await axios.get(`/api/payments/verify/${reference}`)
                this.paymentStatus = response.data.data.status
                
                if (response.data.data.status === 'success') {
                    this.clearBooking()
                }
                
                return response.data
            } catch (error) {
                this.errors = error.response?.data?.message || 'Payment verification failed'
                throw error
            } finally {
                this.loading = false
            }
        },

        clearBooking() {
            this.currentBooking = null
            this.selectedTickets = []
            this.totalAmount = 0
            this.bookingReference = null
            this.paymentStatus = null
            this.currentEvent = null
        }
    }
})