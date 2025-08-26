<template>
  <div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Hero Section with Image -->
    <div class="relative h-96 md:h-[500px] rounded-2xl overflow-hidden mb-8">
      <img 
        v-if="event.media.cover_image"
        :src="event.media.cover_image" 
        :alt="event.title"
        class="w-full h-full object-cover"
      >
      <div v-else class="w-full h-full bg-gradient-to-br from-gray-300 to-gray-400"></div>
      
      <!-- Overlay Gradient -->
      <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
      
      <!-- Event Title Overlay -->
      <div class="absolute bottom-0 left-0 right-0 p-8 text-white">
        <div class="max-w-4xl">
          <div class="flex items-center gap-3 mb-4">
            <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">
              {{ event.category?.name || 'Event' }}
            </span>
            <span v-if="event.is_featured" class="px-3 py-1 bg-yellow-500/80 backdrop-blur-sm rounded-full text-sm font-medium">
              Featured
            </span>
          </div>
          <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ event.title }}</h1>
          <div class="flex flex-wrap items-center gap-6 text-lg">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              {{ event.dates.formatted_date }}
            </div>
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              {{ event.dates.formatted_time }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid lg:grid-cols-3 gap-8">
      <!-- Left Column - Event Details -->
      <div class="lg:col-span-2 space-y-8">
        <!-- Quick Info Cards -->
        <div class="grid sm:grid-cols-2 gap-4">
          <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-start gap-4">
              <div class="p-3 bg-blue-50 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 mb-1">Venue</h3>
                <p class="text-gray-600">{{ event.venue.name }}</p>
                <p class="text-sm text-gray-500">{{ event.venue.address }}, {{ event.venue.city }}</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <div class="flex items-start gap-4">
              <div class="p-3 bg-green-50 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900 mb-1">Organizer</h3>
                <p class="text-gray-600">{{ event.organizer.name }}</p>
                <span v-if="event.organizer.is_verified" class="text-xs text-green-600 font-medium">✓ Verified</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">About This Event</h2>
          <div class="prose prose-gray max-w-none" v-html="event.description"></div>
        </div>

        <!-- Ticket Types -->
        <div v-if="event.pricing.ticket_types && event.pricing.ticket_types.length" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">Ticket Options</h2>
          <div class="space-y-3">
            <div 
              v-for="(ticket, index) in event.pricing.ticket_types" 
              :key="index"
              class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-[#305F64] transition-colors"
            >
              <div>
                <h3 class="font-semibold text-gray-900">{{ ticket.name }}</h3>
                <p v-if="ticket.description" class="text-sm text-gray-600 mt-1">{{ ticket.description }}</p>
              </div>
              <div class="text-right">
                <p class="text-xl font-bold text-[#305F64]">{{ event.pricing.currency }} {{ formatNumber(ticket.price) }}</p>
                <p v-if="ticket.available" class="text-xs text-gray-500">{{ ticket.available }} left</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Terms & Conditions -->
        <div v-if="event.policies.terms_conditions" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">Terms & Conditions</h2>
          <div class="prose prose-sm prose-gray max-w-none" v-html="event.policies.terms_conditions"></div>
        </div>

        <!-- Refund Policy -->
        <div v-if="event.policies.refund_policy" class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">Refund Policy</h2>
          <div class="prose prose-sm prose-gray max-w-none" v-html="event.policies.refund_policy"></div>
        </div>
      </div>

      <!-- Right Column - Booking Card -->
      <div class="lg:col-span-1">
        <div class="sticky top-4">
          <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="p-6">
              <div class="mb-6">
                <div class="flex items-baseline justify-between mb-2">
                  <span class="text-sm text-gray-600">Starting from</span>
                  <span v-if="event.availability.available_tickets" class="text-xs text-green-600 font-medium">
                    {{ event.availability.available_tickets }} tickets left
                  </span>
                </div>
                <p class="text-3xl font-bold text-[#305F64]">
                  {{ event.pricing.currency }} {{ formatNumber(event.pricing.min_price) }}
                </p>
              </div>

              <!-- Availability Bar -->
              <div v-if="event.availability.capacity" class="mb-6">
                <div class="flex justify-between text-xs text-gray-600 mb-2">
                  <span>Availability</span>
                  <span>{{ Math.round((event.availability.tickets_sold / event.availability.capacity) * 100) }}% sold</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div 
                    class="bg-gradient-to-r from-green-500 to-yellow-500 h-2 rounded-full transition-all duration-500"
                    :style="`width: ${Math.min(100, (event.availability.tickets_sold / event.availability.capacity) * 100)}%`"
                  ></div>
                </div>
              </div>

              <!-- Get Tickets Button -->
              <button 
                @click="$emit('select-tickets')"
                :disabled="event.availability.is_sold_out"
                class="w-full py-3 px-6 bg-[#305F64] text-white font-semibold rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ event.availability.is_sold_out ? 'Sold Out' : 'Get Tickets' }}
              </button>

              <!-- Share Buttons -->
              <div class="mt-6 pt-6 border-t border-gray-100">
                <p class="text-sm text-gray-600 mb-3">Share this event</p>
                <div class="flex gap-3">
                  <button @click="shareOnFacebook" class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                  </button>
                  <button @click="shareOnTwitter" class="p-2 bg-blue-50 text-blue-400 rounded-lg hover:bg-blue-100 transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                    </svg>
                  </button>
                  <button @click="shareOnWhatsApp" class="p-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.149-.67.149-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                      <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 16.772c-.258.637-.758 1.162-1.401 1.475-.407.198-.847.298-1.289.298-.267 0-.535-.038-.799-.115-1.253-.364-2.404-1.055-3.426-2.055-.937-.917-1.693-1.994-2.246-3.198-.578-1.254-.872-2.573-.872-3.92 0-.645.095-1.283.283-1.895.313-.643.838-1.144 1.475-1.401.244-.099.499-.148.757-.148.091 0 .183.006.273.018.147.019.293.043.436.095.436.159.798.516.957.949l.524 1.404c.16.428.03.911-.298 1.211l-.561.561c-.075.075-.113.173-.113.275 0 .052.006.104.018.154.024.099.061.194.109.283.243.444.567.85.958 1.207.405.37.852.668 1.332.887.087.04.176.068.268.083.052.009.104.013.155.013.102 0 .2-.038.275-.113l.561-.561c.301-.327.783-.457 1.211-.298l1.404.524c.433.16.79.521.949.957.159.436.131.916-.074 1.35z"/>
                    </svg>
                  </button>
                  <button @click="copyLink" class="p-2 bg-gray-50 text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Age Restriction -->
              <div v-if="event.policies.age_restriction" class="mt-4 p-3 bg-yellow-50 rounded-lg">
                <p class="text-sm text-yellow-800">
                  <strong>Age Restriction:</strong> {{ event.policies.age_restriction }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

// Props
const props = defineProps({
  event: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['select-tickets'])

// Methods
const formatNumber = (num) => {
  return new Intl.NumberFormat().format(num)
}

const shareOnFacebook = () => {
  const url = window.location.href
  window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank')
}

const shareOnTwitter = () => {
  const url = window.location.href
  const text = `Check out ${props.event.title} on Noxxi!`
  window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`, '_blank')
}

const shareOnWhatsApp = () => {
  const url = window.location.href
  const text = `Check out ${props.event.title} on Noxxi: ${url}`
  window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank')
}

const copyLink = () => {
  navigator.clipboard.writeText(window.location.href)
  alert('Link copied to clipboard!')
}
</script>