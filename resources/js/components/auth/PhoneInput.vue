<template>
  <div class="phone-input-wrapper">
    <div class="flex">
      <!-- Country Code Selector -->
      <div class="relative">
        <button
          type="button"
          @click="toggleDropdown"
          class="flex items-center px-3 py-2.5 border border-r-0 border-gray-300 rounded-l-lg bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#305F64] focus:z-10 transition-colors"
        >
          <span class="text-lg mr-1">{{ selectedCountry.flag }}</span>
          <span class="text-sm font-medium">{{ selectedCountry.dialCode }}</span>
          <svg class="w-4 h-4 ml-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
        
        <!-- Country Dropdown -->
        <transition
          enter-active-class="transition ease-out duration-100"
          enter-from-class="transform opacity-0 scale-95"
          enter-to-class="transform opacity-100 scale-100"
          leave-active-class="transition ease-in duration-75"
          leave-from-class="transform opacity-100 scale-100"
          leave-to-class="transform opacity-0 scale-95"
        >
          <div
            v-if="showDropdown"
            class="absolute z-50 mt-1 w-72 bg-white border border-gray-300 rounded-lg shadow-lg"
          >
            <div class="p-2 border-b border-gray-200">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search country..."
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#305F64] focus:border-transparent"
                @click.stop
              >
            </div>
            <div class="max-h-60 overflow-y-auto">
              <button
                v-for="country in filteredCountries"
                :key="country.iso2"
                type="button"
                @click="selectCountry(country)"
                class="w-full text-left px-3 py-2 hover:bg-gray-100 flex items-center text-sm transition-colors"
              >
                <span class="text-lg mr-2">{{ country.flag }}</span>
                <span class="flex-1">{{ country.name }}</span>
                <span class="text-gray-500 text-xs">{{ country.dialCode }}</span>
              </button>
            </div>
          </div>
        </transition>
      </div>
      
      <!-- Phone Number Input -->
      <input
        :value="localValue"
        @input="updateValue"
        type="tel"
        :placeholder="placeholder"
        :required="required"
        class="flex-1 appearance-none block w-full px-3 py-2.5 border border-gray-300 rounded-r-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#305F64] focus:border-transparent focus:z-10 transition-colors"
      >
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  modelValue: String,
  placeholder: {
    type: String,
    default: 'Enter phone number'
  },
  required: {
    type: Boolean,
    default: false
  },
  preferredCountries: {
    type: Array,
    default: () => ['KE', 'NG', 'ZA', 'GH', 'UG', 'TZ', 'EG']
  }
})

const emit = defineEmits(['update:modelValue', 'validate'])

// Countries data with flags and dial codes
const allCountries = [
  { name: 'Kenya', iso2: 'KE', dialCode: '+254', flag: '🇰🇪' },
  { name: 'Nigeria', iso2: 'NG', dialCode: '+234', flag: '🇳🇬' },
  { name: 'South Africa', iso2: 'ZA', dialCode: '+27', flag: '🇿🇦' },
  { name: 'Ghana', iso2: 'GH', dialCode: '+233', flag: '🇬🇭' },
  { name: 'Uganda', iso2: 'UG', dialCode: '+256', flag: '🇺🇬' },
  { name: 'Tanzania', iso2: 'TZ', dialCode: '+255', flag: '🇹🇿' },
  { name: 'Egypt', iso2: 'EG', dialCode: '+20', flag: '🇪🇬' },
  { name: 'Ethiopia', iso2: 'ET', dialCode: '+251', flag: '🇪🇹' },
  { name: 'Rwanda', iso2: 'RW', dialCode: '+250', flag: '🇷🇼' },
  { name: 'Morocco', iso2: 'MA', dialCode: '+212', flag: '🇲🇦' },
  { name: 'Algeria', iso2: 'DZ', dialCode: '+213', flag: '🇩🇿' },
  { name: 'Tunisia', iso2: 'TN', dialCode: '+216', flag: '🇹🇳' },
  { name: 'United States', iso2: 'US', dialCode: '+1', flag: '🇺🇸' },
  { name: 'United Kingdom', iso2: 'GB', dialCode: '+44', flag: '🇬🇧' },
  { name: 'Canada', iso2: 'CA', dialCode: '+1', flag: '🇨🇦' },
  { name: 'Australia', iso2: 'AU', dialCode: '+61', flag: '🇦🇺' },
  { name: 'Germany', iso2: 'DE', dialCode: '+49', flag: '🇩🇪' },
  { name: 'France', iso2: 'FR', dialCode: '+33', flag: '🇫🇷' },
  { name: 'India', iso2: 'IN', dialCode: '+91', flag: '🇮🇳' },
  { name: 'China', iso2: 'CN', dialCode: '+86', flag: '🇨🇳' },
  { name: 'Japan', iso2: 'JP', dialCode: '+81', flag: '🇯🇵' },
  { name: 'Brazil', iso2: 'BR', dialCode: '+55', flag: '🇧🇷' },
  { name: 'Mexico', iso2: 'MX', dialCode: '+52', flag: '🇲🇽' },
  { name: 'Spain', iso2: 'ES', dialCode: '+34', flag: '🇪🇸' },
  { name: 'Italy', iso2: 'IT', dialCode: '+39', flag: '🇮🇹' },
  { name: 'Netherlands', iso2: 'NL', dialCode: '+31', flag: '🇳🇱' },
  { name: 'Sweden', iso2: 'SE', dialCode: '+46', flag: '🇸🇪' },
  { name: 'Norway', iso2: 'NO', dialCode: '+47', flag: '🇳🇴' },
  { name: 'Denmark', iso2: 'DK', dialCode: '+45', flag: '🇩🇰' },
  { name: 'Finland', iso2: 'FI', dialCode: '+358', flag: '🇫🇮' },
  { name: 'Poland', iso2: 'PL', dialCode: '+48', flag: '🇵🇱' },
  { name: 'Russia', iso2: 'RU', dialCode: '+7', flag: '🇷🇺' },
  { name: 'Turkey', iso2: 'TR', dialCode: '+90', flag: '🇹🇷' },
  { name: 'Saudi Arabia', iso2: 'SA', dialCode: '+966', flag: '🇸🇦' },
  { name: 'UAE', iso2: 'AE', dialCode: '+971', flag: '🇦🇪' },
  { name: 'Israel', iso2: 'IL', dialCode: '+972', flag: '🇮🇱' },
  { name: 'Singapore', iso2: 'SG', dialCode: '+65', flag: '🇸🇬' },
  { name: 'Malaysia', iso2: 'MY', dialCode: '+60', flag: '🇲🇾' },
  { name: 'Indonesia', iso2: 'ID', dialCode: '+62', flag: '🇮🇩' },
  { name: 'Philippines', iso2: 'PH', dialCode: '+63', flag: '🇵🇭' },
  { name: 'Thailand', iso2: 'TH', dialCode: '+66', flag: '🇹🇭' },
  { name: 'Vietnam', iso2: 'VN', dialCode: '+84', flag: '🇻🇳' },
  { name: 'South Korea', iso2: 'KR', dialCode: '+82', flag: '🇰🇷' },
  { name: 'Argentina', iso2: 'AR', dialCode: '+54', flag: '🇦🇷' },
  { name: 'Chile', iso2: 'CL', dialCode: '+56', flag: '🇨🇱' },
  { name: 'Colombia', iso2: 'CO', dialCode: '+57', flag: '🇨🇴' },
  { name: 'Peru', iso2: 'PE', dialCode: '+51', flag: '🇵🇪' },
  { name: 'Venezuela', iso2: 'VE', dialCode: '+58', flag: '🇻🇪' },
  { name: 'Ecuador', iso2: 'EC', dialCode: '+593', flag: '🇪🇨' },
  { name: 'Bolivia', iso2: 'BO', dialCode: '+591', flag: '🇧🇴' },
  { name: 'Paraguay', iso2: 'PY', dialCode: '+595', flag: '🇵🇾' },
  { name: 'Uruguay', iso2: 'UY', dialCode: '+598', flag: '🇺🇾' },
  { name: 'Zambia', iso2: 'ZM', dialCode: '+260', flag: '🇿🇲' },
  { name: 'Zimbabwe', iso2: 'ZW', dialCode: '+263', flag: '🇿🇼' },
  { name: 'Botswana', iso2: 'BW', dialCode: '+267', flag: '🇧🇼' },
  { name: 'Namibia', iso2: 'NA', dialCode: '+264', flag: '🇳🇦' },
  { name: 'Mozambique', iso2: 'MZ', dialCode: '+258', flag: '🇲🇿' },
  { name: 'Angola', iso2: 'AO', dialCode: '+244', flag: '🇦🇴' },
  { name: 'Senegal', iso2: 'SN', dialCode: '+221', flag: '🇸🇳' },
  { name: 'Mali', iso2: 'ML', dialCode: '+223', flag: '🇲🇱' },
  { name: 'Burkina Faso', iso2: 'BF', dialCode: '+226', flag: '🇧🇫' },
  { name: 'Ivory Coast', iso2: 'CI', dialCode: '+225', flag: '🇨🇮' },
  { name: 'Cameroon', iso2: 'CM', dialCode: '+237', flag: '🇨🇲' },
  { name: 'Niger', iso2: 'NE', dialCode: '+227', flag: '🇳🇪' },
  { name: 'Chad', iso2: 'TD', dialCode: '+235', flag: '🇹🇩' },
  { name: 'Sudan', iso2: 'SD', dialCode: '+249', flag: '🇸🇩' },
  { name: 'Libya', iso2: 'LY', dialCode: '+218', flag: '🇱🇾' },
  { name: 'Mauritania', iso2: 'MR', dialCode: '+222', flag: '🇲🇷' },
  { name: 'Malawi', iso2: 'MW', dialCode: '+265', flag: '🇲🇼' },
  { name: 'Somalia', iso2: 'SO', dialCode: '+252', flag: '🇸🇴' },
  { name: 'Djibouti', iso2: 'DJ', dialCode: '+253', flag: '🇩🇯' },
  { name: 'Eritrea', iso2: 'ER', dialCode: '+291', flag: '🇪🇷' },
  { name: 'Gabon', iso2: 'GA', dialCode: '+241', flag: '🇬🇦' },
  { name: 'Congo', iso2: 'CG', dialCode: '+242', flag: '🇨🇬' },
  { name: 'DR Congo', iso2: 'CD', dialCode: '+243', flag: '🇨🇩' },
  { name: 'Central African Republic', iso2: 'CF', dialCode: '+236', flag: '🇨🇫' },
  { name: 'Equatorial Guinea', iso2: 'GQ', dialCode: '+240', flag: '🇬🇶' },
  { name: 'Gambia', iso2: 'GM', dialCode: '+220', flag: '🇬🇲' },
  { name: 'Guinea', iso2: 'GN', dialCode: '+224', flag: '🇬🇳' },
  { name: 'Guinea-Bissau', iso2: 'GW', dialCode: '+245', flag: '🇬🇼' },
  { name: 'Liberia', iso2: 'LR', dialCode: '+231', flag: '🇱🇷' },
  { name: 'Sierra Leone', iso2: 'SL', dialCode: '+232', flag: '🇸🇱' },
  { name: 'Togo', iso2: 'TG', dialCode: '+228', flag: '🇹🇬' },
  { name: 'Benin', iso2: 'BJ', dialCode: '+229', flag: '🇧🇯' },
  { name: 'Burundi', iso2: 'BI', dialCode: '+257', flag: '🇧🇮' },
  { name: 'Comoros', iso2: 'KM', dialCode: '+269', flag: '🇰🇲' },
  { name: 'Madagascar', iso2: 'MG', dialCode: '+261', flag: '🇲🇬' },
  { name: 'Mauritius', iso2: 'MU', dialCode: '+230', flag: '🇲🇺' },
  { name: 'Seychelles', iso2: 'SC', dialCode: '+248', flag: '🇸🇨' },
  { name: 'Cape Verde', iso2: 'CV', dialCode: '+238', flag: '🇨🇻' },
  { name: 'Lesotho', iso2: 'LS', dialCode: '+266', flag: '🇱🇸' },
  { name: 'Eswatini', iso2: 'SZ', dialCode: '+268', flag: '🇸🇿' },
]

const showDropdown = ref(false)
const searchQuery = ref('')
const selectedCountry = ref(allCountries.find(c => c.iso2 === 'KE') || allCountries[0])
const localValue = ref('')

// Sort countries with preferred ones at top
const sortedCountries = computed(() => {
  const preferred = []
  const others = []
  
  allCountries.forEach(country => {
    if (props.preferredCountries.includes(country.iso2)) {
      preferred.push(country)
    } else {
      others.push(country)
    }
  })
  
  return [...preferred, ...others.sort((a, b) => a.name.localeCompare(b.name))]
})

const filteredCountries = computed(() => {
  if (!searchQuery.value) return sortedCountries.value
  
  const query = searchQuery.value.toLowerCase()
  return sortedCountries.value.filter(country => 
    country.name.toLowerCase().includes(query) ||
    country.dialCode.includes(query) ||
    country.iso2.toLowerCase().includes(query)
  )
})

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value
  if (showDropdown.value) {
    searchQuery.value = ''
  }
}

const selectCountry = (country) => {
  selectedCountry.value = country
  showDropdown.value = false
  searchQuery.value = ''
  updateFullNumber()
}

const updateValue = (event) => {
  let value = event.target.value.replace(/\D/g, '')
  
  // Remove leading zero if present
  if (value.startsWith('0')) {
    value = value.substring(1)
  }
  
  localValue.value = value
  updateFullNumber()
}

const updateFullNumber = () => {
  const fullNumber = selectedCountry.value.dialCode + localValue.value
  emit('update:modelValue', fullNumber)
  
  // Emit validation status
  const isValid = localValue.value.length >= 7 && localValue.value.length <= 15
  emit('validate', { isValid, formatted: fullNumber })
}

// Handle click outside
const handleClickOutside = (event) => {
  const phoneInput = event.target.closest('.phone-input-wrapper')
  if (!phoneInput) {
    showDropdown.value = false
  }
}

// Initialize with existing value if provided
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    // Try to parse the country code from the number
    const country = allCountries.find(c => newValue.startsWith(c.dialCode))
    if (country) {
      selectedCountry.value = country
      localValue.value = newValue.substring(country.dialCode.length)
    }
  }
}, { immediate: true })

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.phone-input-wrapper {
  position: relative;
}
</style>