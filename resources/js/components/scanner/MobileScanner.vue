<template>
  <div class="h-screen bg-black relative">
    <!-- Simple Header -->
    <div class="absolute top-0 left-0 right-0 z-10 bg-black/50 text-white p-4">
      <div class="flex justify-between items-center">
        <h1 class="text-lg font-semibold">Scan Ticket</h1>
        <span class="text-sm">{{ scanCount }} scanned</span>
      </div>
    </div>

    <!-- Camera View -->
    <video 
      ref="video" 
      class="w-full h-full object-cover"
      autoplay
      playsinline
      webkit-playsinline
      muted
      controls="false"
    ></video>
    
    <!-- Scan Guide -->
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
      <div class="w-64 h-64 border-2 border-white/30 rounded-lg"></div>
    </div>

    <!-- Result Overlay -->
    <transition name="fade">
      <div v-if="lastResult" 
        :class="['absolute bottom-0 left-0 right-0 p-6 text-center text-white', 
                 lastResult.success ? 'bg-green-500' : 'bg-red-500']">
        {{ lastResult.message }}
      </div>
    </transition>

    <!-- Hidden Canvas -->
    <canvas ref="canvas" class="hidden"></canvas>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import jsQR from 'jsqr'

const video = ref(null)
const canvas = ref(null)
const scanCount = ref(0)
const lastResult = ref(null)

let scanning = false
let stream = null

onMounted(() => {
  startScanner()
})

onUnmounted(() => {
  stopScanner()
})

async function startScanner() {
  try {
    // Check browser compatibility
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      throw new Error('Camera API not supported')
    }
    
    // Check HTTPS (required for camera except localhost)
    if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
      throw new Error('HTTPS required for camera access')
    }
    
    // Camera constraints with fallbacks for different browsers
    const constraints = {
      video: { 
        facingMode: 'environment', // Rear camera
        width: { ideal: 1280 },
        height: { ideal: 720 }
      }
    }
    
    // Try with environment camera first, fallback to any camera
    try {
      stream = await navigator.mediaDevices.getUserMedia(constraints)
    } catch (e) {
      // Fallback for browsers that don't support facingMode
      stream = await navigator.mediaDevices.getUserMedia({ video: true })
    }
    
    video.value.srcObject = stream
    
    // Safari iOS compatibility
    if (video.value.playsInline !== undefined) {
      video.value.playsInline = true
    }
    
    video.value.addEventListener('loadedmetadata', () => {
      scanning = true
      scanLoop()
    })
  } catch (err) {
    console.error('Camera error:', err.message)
    // Show user-friendly error
    showResult({ 
      success: false, 
      message: err.message.includes('HTTPS') ? 'HTTPS required' : 'Camera access denied' 
    })
  }
}

function scanLoop() {
  if (!scanning) return
  
  const ctx = canvas.value.getContext('2d')
  canvas.value.width = video.value.videoWidth
  canvas.value.height = video.value.videoHeight
  
  ctx.drawImage(video.value, 0, 0, canvas.value.width, canvas.value.height)
  const imageData = ctx.getImageData(0, 0, canvas.value.width, canvas.value.height)
  
  const code = jsQR(imageData.data, imageData.width, imageData.height)
  
  if (code) {
    processQRCode(code.data)
    // Pause scanning briefly after successful scan
    setTimeout(() => {
      if (scanning) requestAnimationFrame(scanLoop)
    }, 2000)
  } else {
    requestAnimationFrame(scanLoop)
  }
}

async function processQRCode(data) {
  try {
    const response = await fetch('/api/scanner/validate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify({ qr_data: data })
    })
    
    const result = await response.json()
    
    if (result.status === 'success') {
      scanCount.value++
      showResult({ success: true, message: '✓ Checked In' })
    } else {
      showResult({ success: false, message: result.message || 'Invalid ticket' })
    }
  } catch (err) {
    showResult({ success: false, message: 'Network error' })
  }
}

function showResult(result) {
  lastResult.value = result
  setTimeout(() => {
    lastResult.value = null
  }, 2000)
}

function stopScanner() {
  scanning = false
  if (stream) {
    stream.getTracks().forEach(track => track.stop())
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>