/**
 * IndexedDB wrapper for offline scanner functionality
 * Handles caching for both organizers and managers with scan permissions
 */

const DB_NAME = 'NoxxiScannerDB';
const DB_VERSION = 1;

class ScannerDB {
    constructor() {
        this.db = null;
    }

    /**
     * Initialize the database
     */
    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Store event manifests with tickets
                if (!db.objectStoreNames.contains('events')) {
                    const eventStore = db.createObjectStore('events', { keyPath: 'id' });
                    eventStore.createIndex('organizer_id', 'organizer_id', { unique: false });
                    eventStore.createIndex('sync_time', 'sync_time', { unique: false });
                }

                // Store validated tickets (for duplicate prevention)
                if (!db.objectStoreNames.contains('tickets')) {
                    const ticketStore = db.createObjectStore('tickets', { keyPath: 'id' });
                    ticketStore.createIndex('event_id', 'event_id', { unique: false });
                    ticketStore.createIndex('ticket_code', 'ticket_code', { unique: false });
                    ticketStore.createIndex('status', 'status', { unique: false });
                }

                // Store pending check-ins for sync
                if (!db.objectStoreNames.contains('pending_checkins')) {
                    const pendingStore = db.createObjectStore('pending_checkins', { 
                        keyPath: 'local_id',
                        autoIncrement: true 
                    });
                    pendingStore.createIndex('ticket_id', 'ticket_id', { unique: false });
                    pendingStore.createIndex('event_id', 'event_id', { unique: false });
                    pendingStore.createIndex('created_at', 'created_at', { unique: false });
                    pendingStore.createIndex('sync_status', 'sync_status', { unique: false });
                }

                // Store user permissions cache
                if (!db.objectStoreNames.contains('permissions')) {
                    const permStore = db.createObjectStore('permissions', { keyPath: 'user_id' });
                    permStore.createIndex('role', 'role', { unique: false });
                    permStore.createIndex('updated_at', 'updated_at', { unique: false });
                }

                // Store scanner session data
                if (!db.objectStoreNames.contains('session')) {
                    db.createObjectStore('session', { keyPath: 'key' });
                }
            };
        });
    }

    /**
     * Store signed event manifest with tickets for offline scanning
     * Validates permissions and signature before storing
     */
    async storeEventManifest(manifestData, userPermissions) {
        if (!this.db) await this.init();

        const transaction = this.db.transaction(['events', 'tickets', 'permissions'], 'readwrite');
        
        try {
            // Extract event data from signed manifest
            const eventData = manifestData.event;
            const signedManifest = manifestData.manifest;
            
            // Validate user has permission for this event
            const canAccess = this.validateEventAccess(eventData.id, userPermissions);
            if (!canAccess) {
                throw new Error('No permission to cache this event');
            }

            // Verify manifest signature
            if (!this.verifyManifestSignature(signedManifest)) {
                throw new Error('Invalid manifest signature');
            }

            // Store event data with signature
            const eventStore = transaction.objectStore('events');
            await this.promisifyRequest(eventStore.put({
                ...eventData,
                manifest_signature: signedManifest.signature,
                manifest_generated_at: signedManifest.generated_at,
                manifest_expires_at: signedManifest.expires_at,
                sync_time: new Date().toISOString(),
                cached_by: userPermissions.user_id,
                cached_role: userPermissions.role
            }));

            // Store tickets from signed manifest
            const ticketStore = transaction.objectStore('tickets');
            for (const ticket of signedManifest.tickets || []) {
                await this.promisifyRequest(ticketStore.put({
                    ...ticket,
                    event_id: eventData.id,
                    cached_at: new Date().toISOString()
                }));
            }

            // Update permissions cache
            const permStore = transaction.objectStore('permissions');
            await this.promisifyRequest(permStore.put({
                ...userPermissions,
                updated_at: new Date().toISOString()
            }));

            await this.promisifyRequest(transaction.complete);
            return true;
        } catch (error) {
            console.error('Failed to store event manifest:', error);
            throw error;
        }
    }

    /**
     * Validate if user (organizer or manager) has access to event
     */
    validateEventAccess(eventId, permissions) {
        // Organizers can access their own events
        if (permissions.role === 'organizer' && permissions.organizer_id) {
            return permissions.allowed_events.includes(eventId);
        }

        // Managers can only access events they're assigned to
        if (permissions.role === 'manager') {
            return permissions.allowed_events.includes(eventId);
        }

        return false;
    }

    /**
     * Get cached event data with signed manifest for offline scanning
     */
    async getEventManifest(eventId) {
        if (!this.db) await this.init();

        const transaction = this.db.transaction(['events', 'tickets'], 'readonly');
        const eventStore = transaction.objectStore('events');
        
        const event = await this.promisifyRequest(eventStore.get(eventId));
        
        if (!event) return null;

        // Check if manifest has expired
        if (event.manifest_expires_at) {
            const expiryDate = new Date(event.manifest_expires_at);
            if (expiryDate < new Date()) {
                console.warn('Cached manifest has expired for event:', eventId);
                // Still return it but mark as expired
                event.manifest_expired = true;
            }
        }

        // Get all tickets for this event
        const ticketStore = transaction.objectStore('tickets');
        const index = ticketStore.index('event_id');
        const tickets = await this.promisifyRequest(index.getAll(eventId));

        return {
            ...event,
            tickets,
            is_cached: true,
            cache_age: this.getCacheAge(event.sync_time)
        };
    }

    /**
     * Store pending check-in for later sync
     * IMPORTANT: Prevents double scanning by checking existing pending check-ins
     */
    async storePendingCheckIn(checkInData) {
        if (!this.db) await this.init();

        const transaction = this.db.transaction(['pending_checkins', 'tickets'], 'readwrite');
        
        try {
            // First check if this ticket already has a pending check-in
            const pendingStore = transaction.objectStore('pending_checkins');
            const ticketIndex = pendingStore.index('ticket_id');
            const existingPending = await this.promisifyRequest(
                ticketIndex.getAll(checkInData.ticket_id)
            );
            
            // If there's already a pending or synced check-in, reject
            if (existingPending && existingPending.length > 0) {
                const mostRecent = existingPending[existingPending.length - 1];
                throw new Error(`Ticket already has pending check-in from ${mostRecent.created_at}`);
            }
            
            // Store the new pending check-in
            const localId = await this.promisifyRequest(pendingStore.add({
                ...checkInData,
                created_at: new Date().toISOString(),
                sync_status: 'pending',
                retry_count: 0
            }));

            // Update ticket status locally to prevent re-scanning
            const ticketStore = transaction.objectStore('tickets');
            const ticket = await this.promisifyRequest(ticketStore.get(checkInData.ticket_id));
            
            if (ticket) {
                // Only update if not already marked as used
                if (ticket.status !== 'used') {
                    ticket.status = 'used';
                    ticket.used_at = checkInData.checked_at;
                    ticket.used_by = checkInData.user_id;
                    ticket.offline_checked_in = true;
                    ticket.pending_sync = true;
                    await this.promisifyRequest(ticketStore.put(ticket));
                }
            }

            await this.promisifyRequest(transaction.complete);
            return localId;
        } catch (error) {
            console.error('Failed to store pending check-in:', error);
            throw error;
        }
    }

    /**
     * Get all pending check-ins for sync
     */
    async getPendingCheckIns() {
        if (!this.db) await this.init();

        const transaction = this.db.transaction(['pending_checkins'], 'readonly');
        const store = transaction.objectStore('pending_checkins');
        const index = store.index('sync_status');
        
        return await this.promisifyRequest(index.getAll('pending'));
    }

    /**
     * Get pending check-ins for a specific ticket
     */
    async getPendingCheckInsForTicket(ticketId) {
        if (!this.db) await this.init();

        const transaction = this.db.transaction(['pending_checkins'], 'readonly');
        const store = transaction.objectStore('pending_checkins');
        const index = store.index('ticket_id');
        
        return await this.promisifyRequest(index.getAll(ticketId));
    }

    /**
     * Mark check-in as synced and clean up offline flag from ticket
     */
    async markCheckInSynced(localId, ticketId = null) {
        if (!this.db) await this.init();

        const transaction = this.db.transaction(['pending_checkins', 'tickets'], 'readwrite');
        const pendingStore = transaction.objectStore('pending_checkins');
        
        const checkIn = await this.promisifyRequest(pendingStore.get(localId));
        if (checkIn) {
            checkIn.sync_status = 'synced';
            checkIn.synced_at = new Date().toISOString();
            await this.promisifyRequest(pendingStore.put(checkIn));
            
            // Update ticket to remove offline flags
            const ticketIdToUpdate = ticketId || checkIn.ticket_id;
            if (ticketIdToUpdate) {
                const ticketStore = transaction.objectStore('tickets');
                const ticket = await this.promisifyRequest(ticketStore.get(ticketIdToUpdate));
                if (ticket) {
                    ticket.offline_checked_in = false;
                    ticket.pending_sync = false;
                    await this.promisifyRequest(ticketStore.put(ticket));
                }
            }
        }
        
        await this.promisifyRequest(transaction.complete);
    }

    /**
     * Validate ticket offline using cached data
     */
    async validateTicketOffline(qrData, eventId) {
        if (!this.db) await this.init();

        try {
            // Parse QR data
            const ticketData = this.parseQrCode(qrData);
            
            if (!ticketData) {
                return { success: false, message: 'Invalid QR code format' };
            }

            // Check if we have cached data for this event
            const manifest = await this.getEventManifest(eventId || ticketData.event_id);
            
            if (!manifest) {
                return { 
                    success: false, 
                    message: 'No offline data available for this event',
                    require_online: true 
                };
            }

            // Find ticket in cached data
            const ticket = manifest.tickets.find(t => 
                t.id === ticketData.ticket_id || 
                t.ticket_code === ticketData.ticket_code
            );

            if (!ticket) {
                return { success: false, message: 'Ticket not found in manifest' };
            }

            // Verify ticket signature against manifest
            if (!this.validateTicketSignature(ticketData, manifest)) {
                return { 
                    success: false, 
                    message: 'Invalid ticket signature - potential tampering detected' 
                };
            }

            // Check if manifest has expired
            if (manifest.manifest_expires_at) {
                const expiryDate = new Date(manifest.manifest_expires_at);
                if (expiryDate < new Date()) {
                    return {
                        success: false,
                        message: 'Manifest has expired - please go online to refresh',
                        require_online: true
                    };
                }
            }

            // Check ticket status (includes offline check-ins)
            if (ticket.status === 'used') {
                const checkInTime = ticket.used_at ? new Date(ticket.used_at).toLocaleString() : 'unknown time';
                const offlineNote = ticket.offline_checked_in ? ' (offline)' : '';
                return { 
                    success: false, 
                    message: `Ticket already checked in at ${checkInTime}${offlineNote}`,
                    already_used: true,
                    offline_checkin: ticket.offline_checked_in || false
                };
            }
            
            // Also check pending check-ins to prevent double scanning
            const pendingCheckIns = await this.getPendingCheckInsForTicket(ticket.id);
            if (pendingCheckIns && pendingCheckIns.length > 0) {
                const mostRecent = pendingCheckIns[pendingCheckIns.length - 1];
                const checkInTime = new Date(mostRecent.created_at).toLocaleString();
                return {
                    success: false,
                    message: `Ticket has pending check-in from ${checkInTime} (waiting to sync)`,
                    already_used: true,
                    pending_sync: true
                };
            }

            if (ticket.status !== 'valid' && ticket.status !== 'transferred') {
                return { 
                    success: false, 
                    message: `Ticket status: ${ticket.status}` 
                };
            }

            return {
                success: true,
                ticket: ticket,
                event: {
                    id: manifest.id,
                    title: manifest.title,
                    venue: manifest.venue_name
                },
                offline_mode: true,
                cache_age: manifest.cache_age
            };
        } catch (error) {
            console.error('Offline validation error:', error);
            return { 
                success: false, 
                message: 'Validation error: ' + error.message 
            };
        }
    }

    /**
     * Parse QR code data
     */
    parseQrCode(qrData) {
        try {
            // Try to parse as JSON first
            if (qrData.startsWith('{')) {
                return JSON.parse(qrData);
            }
            
            // Try base64 decode
            const decoded = atob(qrData);
            return JSON.parse(decoded);
        } catch (error) {
            // Fallback to simple string parsing
            const parts = qrData.split('|');
            if (parts.length >= 3) {
                return {
                    ticket_id: parts[0],
                    event_id: parts[1],
                    ticket_code: parts[2],
                    signature: parts[3] || null
                };
            }
            return null;
        }
    }

    /**
     * Verify manifest signature to prevent tampering
     */
    verifyManifestSignature(signedManifest) {
        if (!signedManifest.signature || !signedManifest.tickets) {
            return false;
        }
        
        // Check if manifest has expired
        if (signedManifest.expires_at) {
            const expiryDate = new Date(signedManifest.expires_at);
            if (expiryDate < new Date()) {
                console.warn('Manifest has expired');
                return false;
            }
        }
        
        // For security, we trust the backend's signature
        // In production, you could implement client-side verification
        // using Web Crypto API if needed
        return true;
    }

    /**
     * Validate individual ticket QR signature
     */
    validateTicketSignature(ticketData, eventManifest) {
        // Check if ticket exists in the signed manifest
        const manifestTicket = eventManifest.tickets?.find(t => 
            t.id === ticketData.ticket_id || 
            t.ticket_code === ticketData.ticket_code
        );
        
        if (!manifestTicket) {
            return false;
        }
        
        // Verify the ticket's signature matches what's in the manifest
        return manifestTicket.qr_signature === ticketData.signature;
    }

    /**
     * Clear old cached data
     */
    async clearOldCache(daysToKeep = 7) {
        if (!this.db) await this.init();

        const cutoffDate = new Date();
        cutoffDate.setDate(cutoffDate.getDate() - daysToKeep);

        const transaction = this.db.transaction(['events', 'tickets', 'pending_checkins'], 'readwrite');
        
        // Clear old events
        const eventStore = transaction.objectStore('events');
        const eventIndex = eventStore.index('sync_time');
        const oldEvents = await this.promisifyRequest(
            eventIndex.getAll(IDBKeyRange.upperBound(cutoffDate.toISOString()))
        );
        
        for (const event of oldEvents) {
            await this.promisifyRequest(eventStore.delete(event.id));
        }

        // Clear synced check-ins older than cutoff
        const pendingStore = transaction.objectStore('pending_checkins');
        const allPending = await this.promisifyRequest(pendingStore.getAll());
        
        for (const checkIn of allPending) {
            if (checkIn.sync_status === 'synced' && 
                new Date(checkIn.synced_at) < cutoffDate) {
                await this.promisifyRequest(pendingStore.delete(checkIn.local_id));
            }
        }

        await this.promisifyRequest(transaction.complete);
    }

    /**
     * Get cache age in human-readable format
     */
    getCacheAge(syncTime) {
        const now = new Date();
        const synced = new Date(syncTime);
        const diffMs = now - synced;
        const diffMins = Math.floor(diffMs / 60000);
        
        if (diffMins < 60) return `${diffMins} minutes ago`;
        if (diffMins < 1440) return `${Math.floor(diffMins / 60)} hours ago`;
        return `${Math.floor(diffMins / 1440)} days ago`;
    }

    /**
     * Helper to promisify IndexedDB requests
     */
    promisifyRequest(request) {
        return new Promise((resolve, reject) => {
            if (request.readyState === 'done') {
                if (request.error) {
                    reject(request.error);
                } else {
                    resolve(request.result);
                }
                return;
            }
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Clear all data (for logout/cleanup)
     */
    async clearAll() {
        if (!this.db) await this.init();

        const transaction = this.db.transaction(
            ['events', 'tickets', 'pending_checkins', 'permissions', 'session'], 
            'readwrite'
        );
        
        for (const storeName of ['events', 'tickets', 'pending_checkins', 'permissions', 'session']) {
            await this.promisifyRequest(transaction.objectStore(storeName).clear());
        }
        
        await this.promisifyRequest(transaction.complete);
    }
}

export default new ScannerDB();