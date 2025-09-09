<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="close"></div>
    
    <!-- Modal -->
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative bg-white rounded-lg max-w-4xl w-full shadow-xl transform transition-all">
        
        <!-- Close button -->
        <button @click="close" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 z-20">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
        
        <!-- Horizontal Ticket Design -->
        <div class="ticket-container flex flex-col md:flex-row">
          <!-- Left Side - Event Info -->
          <div class="md:w-2/3 relative">
            <!-- Header with gradient overlay -->
            <div class="relative h-48 md:h-full md:min-h-[250px] bg-gradient-to-br from-[#305F64] to-[#223338] rounded-t-lg md:rounded-l-lg md:rounded-tr-none">
              <!-- Event Details -->
              <div class="p-6 flex flex-col justify-between h-full text-white">
                <!-- Event Title and Venue -->
                <div>
                  <div class="mb-4 flex gap-2">
                    <span class="inline-flex px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-xs font-medium uppercase tracking-wide">
                      {{ ticket.ticket_type }}
                    </span>
                    <span v-if="ticket.event?.category?.name" class="inline-flex px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-xs font-medium uppercase tracking-wide">
                      {{ ticket.event.category.name }}
                    </span>
                  </div>
                  <h2 class="text-4xl md:text-5xl font-bold mb-2 font-silk">{{ ticket.event?.title || 'Event' }}</h2>
                  <p v-if="ticket.event?.organizer?.business_name" class="text-sm opacity-90">by {{ ticket.event.organizer.business_name }}</p>
                </div>
                
                <!-- Date, Time and Location -->
                <div class="space-y-3">
                  <div class="flex items-center">
                    <svg class="h-5 w-5 mr-3 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="font-medium">{{ formatDate(ticket.event?.event_date) }}</span>
                  </div>
                  
                  <div class="flex items-center">
                    <svg class="h-5 w-5 mr-3 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>{{ ticket.event?.venue_address || ticket.event?.city }}</span>
                  </div>
                  
                  <!-- Additional Details Grid -->
                  <div class="grid grid-cols-2 gap-4 pt-4 border-t border-white/30">
                    <div>
                      <p class="text-xs opacity-75">Ticket Code</p>
                      <p class="font-mono font-bold text-lg">{{ ticket.ticket_code }}</p>
                    </div>
                    <div>
                      <p class="text-xs opacity-75">Price</p>
                      <p class="font-bold text-lg">{{ ticket.currency }} {{ formatPrice(ticket.price) }}</p>
                    </div>
                    <div v-if="ticket.seat_number">
                      <p class="text-xs opacity-75">Seat</p>
                      <p class="font-bold">{{ ticket.seat_section ? ticket.seat_section + ' - ' : '' }}{{ ticket.seat_number }}</p>
                    </div>
                    <div v-if="ticket.holder_name">
                      <p class="text-xs opacity-75">Ticket Holder</p>
                      <p class="font-bold">{{ ticket.holder_name }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Right Side - QR Code and Actions -->
          <div class="md:w-1/3 p-6 bg-gray-50 rounded-b-lg md:rounded-r-lg md:rounded-bl-none flex flex-col items-center justify-center">
            <!-- Status Text -->
            <div class="mb-4 text-sm font-bold uppercase tracking-wide text-black">
              {{ ticket.status === 'valid' ? '✓ Valid Ticket' : 
                 ticket.status === 'used' ? 'Used' : 
                 ticket.status }}
            </div>
            
            <!-- QR Code -->
            <div class="bg-white p-4 rounded-xl shadow-lg mb-4">
              <div v-if="qrCodeLoading" class="w-40 h-40 flex items-center justify-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#305F64]"></div>
              </div>
              <img 
                v-else-if="qrCodeImage" 
                :src="qrCodeImage" 
                alt="Ticket QR Code" 
                class="w-40 h-40"
                @error="handleQrError"
              >
              <div v-else class="w-40 h-40 bg-gray-100 rounded flex items-center justify-center text-gray-500">
                <div class="text-center">
                  <svg class="w-16 h-16 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2l-2-2M5 16h2l-2 2M12 20h.01M12 8h.01"></path>
                  </svg>
                  <span class="text-sm">QR Code</span>
                </div>
              </div>
            </div>
            
            <!-- Booking Reference -->
            <p class="text-xs text-gray-500 text-center">
              Booking Ref: <span class="font-mono font-medium">{{ ticket.booking?.booking_reference || 'N/A' }}</span>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'

const props = defineProps({
  ticket: {
    type: Object,
    required: true
  },
  isOpen: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const qrCodeLoading = ref(false)
const qrCodeError = ref(false)
const qrCodeImage = ref(null)
const qrExpiresAt = ref(null)

// Format date
const formatDate = (dateString) => {
  if (!dateString) return 'Date TBD'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Format price
const formatPrice = (price) => {
  return parseFloat(price).toLocaleString('en-US', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  })
}

// Handle QR code error
const handleQrError = () => {
  qrCodeError.value = true
}

// Close modal
const close = () => {
  emit('close')
}


// Load QR code on-demand when modal opens (secure, never stored)
const loadQrCode = async () => {
  if (!props.ticket?.id) return
  
  qrCodeLoading.value = true
  qrCodeError.value = false
  
  try {
    // Fetch QR code on-demand (generated fresh, never stored)
    const response = await fetch(`/user/tickets/${props.ticket.id}/qr`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      credentials: 'include'
    })
    
    if (response.ok) {
      const result = await response.json()
      if (result.status === 'success' && result.data) {
        qrCodeImage.value = result.data.qr_image
        qrExpiresAt.value = result.data.expires_at
      }
    } else if (response.status === 429) {
      // Rate limited
      qrCodeError.value = true
    } else {
      qrCodeError.value = true
    }
  } catch (error) {
    qrCodeError.value = true
  } finally {
    qrCodeLoading.value = false
  }
}

// Load QR when modal opens
watch(() => props.isOpen, async (newVal) => {
  if (newVal && props.ticket) {
    await loadQrCode()
  }
  
  // Clear QR when modal closes (security)
  if (!newVal) {
    qrCodeImage.value = null
    qrExpiresAt.value = null
  }
})

// Also try loading on mount if modal is already open
onMounted(() => {
  if (props.isOpen && props.ticket) {
    loadQrCode()
  }
})
</script>

<style scoped>
/* Smooth transitions */
.ticket-container {
  animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Perforated edge effect */
.ticket-container::after {
  content: "";
  position: absolute;
  right: 33.33%;
  top: 0;
  bottom: 0;
  width: 2px;
  background-image: repeating-linear-gradient(
    to bottom,
    transparent,
    transparent 8px,
    #e5e7eb 8px,
    #e5e7eb 16px
  );
  display: none;
}

@media (min-width: 768px) {
  .ticket-container::after {
    display: block;
  }
}
</style>