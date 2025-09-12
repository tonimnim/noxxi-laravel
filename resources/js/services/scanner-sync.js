/**
 * Scanner Sync Service
 * Handles syncing offline check-ins when connection is restored
 */

import scannerDB from '../utils/scanner-db';

class ScannerSyncService {
    constructor() {
        this.syncInProgress = false;
        this.syncInterval = null;
        this.onlineListener = null;
    }

    /**
     * Initialize sync service
     */
    init() {
        // Listen for online/offline events
        this.onlineListener = () => {
            if (navigator.onLine) {
                console.log('Connection restored - syncing pending check-ins');
                this.syncPendingCheckIns();
            }
        };
        
        window.addEventListener('online', this.onlineListener);
        
        // Start periodic sync (every 30 seconds when online)
        this.startPeriodicSync();
    }

    /**
     * Start periodic sync
     */
    startPeriodicSync() {
        this.syncInterval = setInterval(() => {
            if (navigator.onLine && !this.syncInProgress) {
                this.syncPendingCheckIns();
            }
        }, 30000); // 30 seconds
    }

    /**
     * Stop periodic sync
     */
    stopPeriodicSync() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
            this.syncInterval = null;
        }
        
        if (this.onlineListener) {
            window.removeEventListener('online', this.onlineListener);
        }
    }

    /**
     * Sync all pending check-ins
     */
    async syncPendingCheckIns() {
        if (this.syncInProgress) return;
        
        this.syncInProgress = true;
        
        try {
            const pendingCheckIns = await scannerDB.getPendingCheckIns();
            
            if (pendingCheckIns.length === 0) {
                return { success: true, synced: 0 };
            }

            console.log(`Syncing ${pendingCheckIns.length} pending check-ins`);
            
            const results = {
                success: true,
                synced: 0,
                failed: 0,
                errors: []
            };

            // Process check-ins in batches to avoid overwhelming the server
            const batchSize = 5;
            for (let i = 0; i < pendingCheckIns.length; i += batchSize) {
                const batch = pendingCheckIns.slice(i, i + batchSize);
                
                await Promise.all(batch.map(async (checkIn) => {
                    try {
                        const success = await this.syncSingleCheckIn(checkIn);
                        if (success) {
                            results.synced++;
                            // Pass ticket_id to properly clean up offline flags
                            await scannerDB.markCheckInSynced(checkIn.local_id, checkIn.ticket_id);
                        } else {
                            results.failed++;
                        }
                    } catch (error) {
                        results.failed++;
                        results.errors.push({
                            ticket_id: checkIn.ticket_id,
                            error: error.message
                        });
                    }
                }));
            }

            // Clean up old synced data
            if (results.synced > 0) {
                await scannerDB.clearOldCache(7); // Keep 7 days of data
            }

            return results;
        } catch (error) {
            console.error('Sync failed:', error);
            return { 
                success: false, 
                error: error.message 
            };
        } finally {
            this.syncInProgress = false;
        }
    }

    /**
     * Sync a single check-in
     */
    async syncSingleCheckIn(checkIn) {
        try {
            const response = await fetch('/scanner/check-in', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    ticket_id: checkIn.ticket_id,
                    event_id: checkIn.event_id,
                    force: true, // Force check-in since it was already validated offline
                    offline_check_in: true,
                    offline_timestamp: checkIn.checked_at
                })
            });

            if (response.ok) {
                const data = await response.json();
                
                // Handle duplicate check-ins gracefully
                if (data.duplicate) {
                    console.log(`Ticket ${checkIn.ticket_id} already checked in`);
                    return true; // Consider it synced
                }
                
                return data.success;
            }

            // Handle specific error cases
            if (response.status === 409) { // Conflict - already checked in
                return true; // Consider it synced
            }

            if (response.status === 403) { // Permission denied
                console.error(`No permission to sync ticket ${checkIn.ticket_id}`);
                return false; // Will retry later
            }

            return false;
        } catch (error) {
            console.error('Failed to sync check-in:', error);
            
            // Increment retry count
            checkIn.retry_count = (checkIn.retry_count || 0) + 1;
            
            // If too many retries, mark as failed
            if (checkIn.retry_count > 5) {
                console.error(`Too many retries for ticket ${checkIn.ticket_id}`);
                return false;
            }
            
            throw error;
        }
    }

    /**
     * Download event manifest for offline use
     */
    async downloadEventManifest(eventId, userPermissions) {
        try {
            const response = await fetch(`/scanner/event/${eventId}/manifest`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`Failed to download manifest: ${response.statusText}`);
            }

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to download manifest');
            }

            // Store signed manifest in IndexedDB with user permissions
            await scannerDB.storeEventManifest(data, userPermissions);

            return {
                success: true,
                message: `Event data cached for offline use`,
                ticket_count: data.manifest?.tickets?.length || 0
            };
        } catch (error) {
            console.error('Failed to download event manifest:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    /**
     * Get CSRF token
     */
    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    /**
     * Get sync status
     */
    async getSyncStatus() {
        const pendingCheckIns = await scannerDB.getPendingCheckIns();
        
        return {
            pending_count: pendingCheckIns.length,
            is_syncing: this.syncInProgress,
            is_online: navigator.onLine,
            oldest_pending: pendingCheckIns.length > 0 
                ? pendingCheckIns[0].created_at 
                : null
        };
    }

    /**
     * Force sync now
     */
    async forceSyncNow() {
        if (!navigator.onLine) {
            return {
                success: false,
                message: 'No internet connection'
            };
        }

        return await this.syncPendingCheckIns();
    }

    /**
     * Clear all pending check-ins
     */
    async clearPendingCheckIns() {
        // This should only be used in emergency or after confirming with user
        const pending = await scannerDB.getPendingCheckIns();
        
        if (pending.length > 0 && !confirm(`Clear ${pending.length} pending check-ins? This cannot be undone.`)) {
            return false;
        }

        await scannerDB.clearAll();
        return true;
    }
}

export default new ScannerSyncService();