/**
 * Scanner Service Worker
 * Provides offline functionality for the ticket scanner
 */

const CACHE_NAME = 'noxxi-scanner-v1';
const SCANNER_CACHE = 'noxxi-scanner-data-v1';

// Cache essential scanner resources
const SCANNER_ASSETS = [
  '/scanner/check-in',
  '/js/scanner-app.js',
  '/css/app.css',
  // Add other critical scanner assets
];

// Install event - cache essential resources
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Scanner SW: Caching essential resources');
        return cache.addAll(SCANNER_ASSETS);
      })
      .catch(error => {
        console.error('Scanner SW: Failed to cache resources:', error);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME && cacheName !== SCANNER_CACHE) {
            console.log('Scanner SW: Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Fetch event - handle offline requests
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Handle scanner API requests
  if (url.pathname.startsWith('/scanner/')) {
    event.respondWith(handleScannerRequest(request));
    return;
  }

  // Handle static assets
  if (request.method === 'GET' && 
      (url.pathname.startsWith('/js/') || 
       url.pathname.startsWith('/css/') ||
       url.pathname === '/scanner/check-in')) {
    event.respondWith(
      caches.match(request)
        .then(response => {
          return response || fetch(request);
        })
    );
  }
});

// Handle scanner-specific requests
async function handleScannerRequest(request) {
  const url = new URL(request.url);
  
  try {
    // Try network first for scanner API requests
    const response = await fetch(request);
    
    // Cache successful responses
    if (response.ok && request.method === 'GET') {
      const cache = await caches.open(SCANNER_CACHE);
      cache.put(request, response.clone());
    }
    
    return response;
  } catch (error) {
    // Network failed - handle offline scenarios
    console.log('Scanner SW: Network failed, handling offline:', url.pathname);
    
    if (request.method === 'POST') {
      return handleOfflinePOST(request);
    }
    
    // Try to serve from cache for GET requests
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Return offline fallback
    return new Response(
      JSON.stringify({
        success: false,
        message: 'No network connection and no cached data available',
        offline: true
      }),
      {
        status: 503,
        headers: {
          'Content-Type': 'application/json'
        }
      }
    );
  }
}

// Handle offline POST requests (validation and check-in)
async function handleOfflinePOST(request) {
  const url = new URL(request.url);
  
  try {
    const body = await request.clone().json();
    
    if (url.pathname === '/scanner/validate') {
      return handleOfflineValidation(body);
    }
    
    if (url.pathname === '/scanner/check-in') {
      return handleOfflineCheckIn(body);
    }
    
    // For other POST requests, return offline error
    return new Response(
      JSON.stringify({
        success: false,
        message: 'This action requires an internet connection',
        offline: true
      }),
      {
        status: 503,
        headers: {
          'Content-Type': 'application/json'
        }
      }
    );
  } catch (error) {
    return new Response(
      JSON.stringify({
        success: false,
        message: 'Invalid request data',
        offline: true
      }),
      {
        status: 400,
        headers: {
          'Content-Type': 'application/json'
        }
      }
    );
  }
}

// Handle offline validation - delegate to IndexedDB via message
async function handleOfflineValidation(body) {
  // Send message to main thread to handle validation using IndexedDB
  const clients = await self.clients.matchAll();
  
  if (clients.length > 0) {
    // Send message to the active client
    clients[0].postMessage({
      type: 'OFFLINE_VALIDATION',
      data: body
    });
    
    // Return a response indicating offline mode
    return new Response(
      JSON.stringify({
        success: false,
        message: 'Validating offline - please wait',
        offline_mode: true,
        require_client_validation: true
      }),
      {
        status: 202,
        headers: {
          'Content-Type': 'application/json'
        }
      }
    );
  }
  
  return new Response(
    JSON.stringify({
      success: false,
      message: 'Offline validation not available',
      offline: true
    }),
    {
      status: 503,
      headers: {
        'Content-Type': 'application/json'
      }
    }
  );
}

// Handle offline check-in - delegate to IndexedDB via message
async function handleOfflineCheckIn(body) {
  // Send message to main thread to handle check-in using IndexedDB
  const clients = await self.clients.matchAll();
  
  if (clients.length > 0) {
    clients[0].postMessage({
      type: 'OFFLINE_CHECKIN',
      data: body
    });
    
    return new Response(
      JSON.stringify({
        success: true,
        message: 'Ticket checked in offline - will sync when online',
        offline_mode: true,
        ticket: body.ticket || {},
        check_in_time: new Date().toISOString()
      }),
      {
        status: 200,
        headers: {
          'Content-Type': 'application/json'
        }
      }
    );
  }
  
  return new Response(
    JSON.stringify({
      success: false,
      message: 'Offline check-in not available',
      offline: true
    }),
    {
      status: 503,
      headers: {
        'Content-Type': 'application/json'
      }
    }
  );
}

// Background sync for pending check-ins
self.addEventListener('sync', event => {
  if (event.tag === 'sync-check-ins') {
    event.waitUntil(syncPendingCheckIns());
  }
});

async function syncPendingCheckIns() {
  // Send message to clients to trigger sync
  const clients = await self.clients.matchAll();
  
  clients.forEach(client => {
    client.postMessage({
      type: 'SYNC_PENDING_CHECKINS'
    });
  });
}

// Handle messages from the main thread
self.addEventListener('message', event => {
  const { type, data } = event.data;
  
  if (type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (type === 'CLAIM_CLIENTS') {
    self.clients.claim();
  }
});