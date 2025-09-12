<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Ticket;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SecureQrService
{
    /**
     * Generate QR code data on-demand (never stored on disk)
     *
     * @throws \Exception
     */
    public function generateSecureQrCode(Ticket $ticket, bool $verifyOwnership = true): array
    {
        // Security: Verify ownership
        if ($verifyOwnership && Auth::check()) {
            if ($ticket->assigned_to !== Auth::id()) {
                Log::warning('Unauthorized QR access attempt', [
                    'ticket_id' => $ticket->id,
                    'user_id' => Auth::id(),
                    'owner_id' => $ticket->assigned_to,
                ]);
                throw new \Exception('Unauthorized access to ticket QR code');
            }
        }

        // Load event if not loaded
        if (! $ticket->relationLoaded('event')) {
            $ticket->load('event');
        }

        // Generate time-limited QR data with signature
        $qrData = $this->generateSecureQrData($ticket);

        // Generate QR image in memory (never saved to disk)
        $qrImage = $this->generateQrImage($qrData['encoded']);

        // Log QR generation for audit trail
        $this->logQrGeneration($ticket);

        return [
            'data' => $qrData['encoded'],
            'image_base64' => 'data:image/png;base64,'.base64_encode($qrImage),
            'expires_at' => $qrData['expires_at'],
            'signature' => $qrData['signature'],
        ];
    }

    /**
     * Generate secure QR data with intelligent time-limited signature
     * Expiry logic based on listing type (event, service, recurring)
     */
    private function generateSecureQrData(Ticket $ticket): array
    {
        // Calculate smart expiry based on listing type
        $expiresAt = $this->calculateQrExpiry($ticket);

        $data = [
            'tid' => $ticket->id,
            'eid' => $ticket->event_id,
            'code' => $ticket->ticket_code,
            'type' => $ticket->ticket_type,
            'exp' => $expiresAt->timestamp,
            'iat' => now()->timestamp,
            'nonce' => bin2hex(random_bytes(8)), // Prevent replay attacks
        ];

        // Generate secure signature
        $signature = $this->generateSignature($data, $ticket->event);

        // Encode for QR
        $encoded = base64_encode(json_encode([
            'data' => $data,
            'sig' => $signature,
        ]));

        return [
            'encoded' => $encoded,
            'signature' => $signature,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /**
     * Calculate intelligent QR expiry based on listing type
     * Uses modern PHP 8 match expression for cleaner logic
     */
    private function calculateQrExpiry(Ticket $ticket): \Carbon\Carbon
    {
        $event = $ticket->event;

        return match ($event->listing_type) {
            // Services (spa, salon, etc): Valid for 1 year from generation
            'service' => now()->addYear(),

            // Recurring events: Valid until 24 hours after last occurrence
            'recurring' => $this->calculateRecurringExpiry($event),

            // Standard events: Valid from 48hrs before until 24hrs after event ends
            'event' => $this->calculateEventExpiry($event),
            default => $this->calculateEventExpiry($event)
        };
    }

    /**
     * Calculate expiry for standard events
     * QR becomes valid 48 hours before and expires 24 hours after
     */
    private function calculateEventExpiry(Event $event): \Carbon\Carbon
    {
        // Handle events without dates (shouldn't happen, but defensive)
        if (! $event->event_date) {
            Log::warning('Event without date found, using 30-day expiry', [
                'event_id' => $event->id,
                'listing_type' => $event->listing_type,
            ]);

            return now()->addDays(30);
        }

        // Use end_date if available (multi-day events), otherwise use event_date
        $eventEndDate = $event->end_date ?? $event->event_date;

        // Check if QR is being generated very early (more than 48hrs before event)
        $validityStartTime = $event->event_date->copy()->subHours(48);
        $currentTime = now();

        if ($currentTime->lt($validityStartTime)) {
            // For early birds: QR will activate 48hrs before event
            // But we still set expiry to 24hrs after event ends
            Log::info('Early QR generation detected', [
                'event_id' => $event->id,
                'event_date' => $event->event_date->toIso8601String(),
                'generation_time' => $currentTime->toIso8601String(),
            ]);
        }

        // QR expires 24 hours after the event ends
        return $eventEndDate->copy()->addDay();
    }

    /**
     * Calculate expiry for recurring events
     * Uses the next occurrence or end_date
     */
    private function calculateRecurringExpiry(Event $event): \Carbon\Carbon
    {
        // If recurring event has an end_date, use that
        if ($event->end_date) {
            return $event->end_date->copy()->addDay();
        }

        // If no end_date but has event_date, treat as ongoing service
        if ($event->event_date) {
            // Find next occurrence (simplified - you might have recurrence rules)
            return $event->event_date->copy()->addMonth();
        }

        // Fallback for recurring without dates: 3 months validity
        return now()->addMonths(3);
    }

    /**
     * Generate HMAC signature for QR data
     */
    private function generateSignature(array $data, ?Event $event): string
    {
        // Use event-specific secret or app key
        $secret = $event->qr_secret_key ?? config('app.key');

        // Sort data for consistent hashing
        ksort($data);

        // Generate HMAC-SHA256 signature
        return hash_hmac('sha256', json_encode($data), $secret);
    }

    /**
     * Generate QR image in memory
     */
    private function generateQrImage(string $data): string
    {
        try {
            // In Endroid v6, all configuration is done through constructor
            $qrCode = new QrCode(
                data: $data,
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::High,
                size: 400,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(0, 0, 0),
                backgroundColor: new Color(255, 255, 255)
            );

            $writer = new PngWriter;
            $result = $writer->write($qrCode);

            return $result->getString();
        } catch (\Exception $e) {
            \Log::error('QR image generation failed', [
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Unable to generate QR image: '.$e->getMessage());
        }
    }

    /**
     * Validate QR code data and signature with intelligent timing checks
     */
    public function validateQrCode(string $qrContent): array
    {
        try {
            $decoded = json_decode(base64_decode($qrContent), true);

            if (! $decoded || ! isset($decoded['data']) || ! isset($decoded['sig'])) {
                return ['success' => false, 'message' => 'Invalid QR format'];
            }

            $data = $decoded['data'];
            $signature = $decoded['sig'];

            // Check expiry
            if (isset($data['exp']) && $data['exp'] < time()) {
                return ['success' => false, 'message' => 'QR code expired. Please refresh your ticket.'];
            }

            // Load ticket and event for signature verification
            $ticket = Ticket::with('event')->find($data['tid']);
            if (! $ticket) {
                return ['success' => false, 'message' => 'Ticket not found'];
            }

            // Verify signature
            $expectedSignature = $this->generateSignature($data, $ticket->event);
            if (! hash_equals($expectedSignature, $signature)) {
                Log::warning('QR signature mismatch', [
                    'ticket_id' => $data['tid'],
                    'provided_sig' => $signature,
                ]);

                return ['success' => false, 'message' => 'Invalid signature'];
            }

            // Check if event hasn't started yet (for events with dates)
            if ($ticket->event->listing_type === 'event' && $ticket->event->event_date) {
                $validityStartTime = $ticket->event->event_date->copy()->subHours(48);

                if (now()->lt($validityStartTime)) {
                    return [
                        'success' => false,
                        'message' => 'Check-in opens 48 hours before the event',
                        'opens_at' => $validityStartTime->toIso8601String(),
                        'event_date' => $ticket->event->event_date->toIso8601String(),
                    ];
                }
            }

            // Check if ticket is already used
            if ($ticket->status === 'used') {
                return [
                    'success' => false,
                    'message' => 'Ticket already used',
                    'used_at' => $ticket->used_at?->toIso8601String(),
                    'entry_gate' => $ticket->entry_gate,
                ];
            }

            // Check if ticket is cancelled
            if ($ticket->status === 'cancelled') {
                return [
                    'success' => false,
                    'message' => 'This ticket has been cancelled',
                ];
            }

            // Return success with ticket details
            return [
                'success' => true,
                'message' => 'Ticket is valid',
                'ticket' => [
                    'id' => $ticket->id,
                    'code' => $ticket->ticket_code,
                    'type' => $ticket->ticket_type,
                    'holder_name' => $ticket->holder_name,
                    'holder_email' => $ticket->holder_email,
                    'seat_number' => $ticket->seat_number,
                    'seat_section' => $ticket->seat_section,
                ],
                'event' => [
                    'id' => $ticket->event->id,
                    'title' => $ticket->event->title,
                    'date' => $ticket->event->event_date->toIso8601String(),
                    'venue' => $ticket->event->venue_name,
                ],
                'can_check_in' => true,
                'gate_id' => $gateId,
            ];

        } catch (\Exception $e) {
            Log::error('QR validation error', ['error' => $e->getMessage()]);

            return ['success' => false, 'message' => 'Validation failed'];
        }
    }

    /**
     * Log QR generation for audit trail
     */
    private function logQrGeneration(Ticket $ticket): void
    {
        // Log to cache for rate limiting
        $key = 'qr_generated_'.$ticket->id.'_'.(Auth::id() ?? 'anonymous');
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, 3600); // Track for 1 hour

        // Log to file for audit
        Log::info('QR code generated', [
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Check if user has exceeded QR generation rate limit
     */
    public function checkRateLimit(Ticket $ticket): bool
    {
        $key = 'qr_generated_'.$ticket->id.'_'.(Auth::id() ?? 'anonymous');
        $attempts = Cache::get($key, 0);

        // Allow 10 QR generations per hour per ticket
        return $attempts < 10;
    }

    /**
     * Generate offline manifest for event scanning with signature
     * This allows scanners to validate tickets offline securely
     */
    public function generateOfflineManifest(Event $event): array
    {
        // Get all valid tickets for this event
        $tickets = Ticket::where('event_id', $event->id)
            ->whereIn('status', ['valid', 'transferred'])
            ->select(['id', 'ticket_code', 'holder_name', 'ticket_type', 'status', 'seat_number', 'seat_section'])
            ->get();

        $manifest = [];

        foreach ($tickets as $ticket) {
            // Generate signature for each ticket for offline validation
            $ticketSignature = $this->generateSignature([
                'ticket_id' => $ticket->id,
                'event_id' => $event->id,
                'ticket_code' => $ticket->ticket_code,
            ], $event);

            $manifest[] = [
                'id' => $ticket->id,
                'code' => $ticket->ticket_code,
                'holder' => $ticket->holder_name,
                'type' => $ticket->ticket_type,
                'status' => $ticket->status,
                'seat' => $ticket->seat_number,
                'section' => $ticket->seat_section,
                'signature' => $ticketSignature,
            ];
        }

        // Create manifest data
        $manifestData = [
            'version' => '2.0', // Bumped version for signed manifests
            'generated_at' => now()->toIso8601String(),
            'expires_at' => now()->addHours(24)->toIso8601String(), // Manifest expires after 24 hours
            'event_id' => $event->id,
            'event_title' => $event->title,
            'event_date' => $event->event_date?->toIso8601String(),
            'listing_type' => $event->listing_type,
            'total_tickets' => count($manifest),
            'tickets' => $manifest,
        ];

        // Sign the entire manifest to prevent tampering
        $manifestSignature = $this->generateManifestSignature($manifestData, $event);

        return [
            'data' => $manifestData,
            'signature' => $manifestSignature,
            'secret' => substr(hash('sha256', $event->qr_secret_key ?? config('app.key')), 0, 16), // Partial secret for offline validation
        ];
    }

    /**
     * Generate signature for entire manifest
     */
    private function generateManifestSignature(array $manifestData, Event $event): string
    {
        // Sort data for consistent hashing
        ksort($manifestData);

        // Use event secret or app key
        $secret = $event->qr_secret_key ?? config('app.key');

        // Create a deterministic string representation
        $dataString = json_encode($manifestData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Generate HMAC-SHA256 signature
        return hash_hmac('sha256', $dataString, $secret.':manifest');
    }

    /**
     * Verify manifest signature to ensure it hasn't been tampered
     */
    public function verifyManifestSignature(array $manifestData, string $signature, Event $event): bool
    {
        $expectedSignature = $this->generateManifestSignature($manifestData, $event);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Check in a ticket (mark as used)
     */
    public function checkInTicket(string $ticketId, string $userId, ?string $gateId = null, ?string $deviceId = null): array
    {
        try {
            $ticket = Ticket::with(['event', 'booking.user'])->find($ticketId);

            if (! $ticket) {
                return [
                    'success' => false,
                    'message' => 'Ticket not found',
                ];
            }

            // Check if ticket is already used
            if ($ticket->status === 'used') {
                return [
                    'success' => false,
                    'message' => 'Ticket already checked in',
                    'used_at' => $ticket->used_at?->toIso8601String(),
                    'entry_gate' => $ticket->entry_gate,
                ];
            }

            // Check if ticket is cancelled
            if ($ticket->status === 'cancelled') {
                return [
                    'success' => false,
                    'message' => 'This ticket has been cancelled',
                ];
            }

            // Check if ticket has expired
            if ($ticket->status === 'expired') {
                return [
                    'success' => false,
                    'message' => 'This ticket has expired',
                ];
            }

            // Check if check-in is enabled for this event
            if (! $ticket->event->check_in_enabled) {
                return [
                    'success' => false,
                    'message' => 'Check-in is not enabled for this event',
                ];
            }

            // Check organizer-defined check-in window
            if (! $ticket->event->allow_immediate_check_in) {
                // Check if check-in window has opened
                if ($ticket->event->check_in_opens_at && $ticket->event->check_in_opens_at->isFuture()) {
                    return [
                        'success' => false,
                        'message' => 'Check-in has not opened yet',
                        'opens_at' => $ticket->event->check_in_opens_at->toIso8601String(),
                    ];
                }

                // Check if check-in window has closed
                if ($ticket->event->check_in_closes_at && $ticket->event->check_in_closes_at->isPast()) {
                    return [
                        'success' => false,
                        'message' => 'Check-in window has closed',
                        'closed_at' => $ticket->event->check_in_closes_at->toIso8601String(),
                    ];
                }
            }

            // Check ticket validity period
            if ($ticket->valid_from && $ticket->valid_from->isFuture()) {
                return [
                    'success' => false,
                    'message' => 'Ticket is not yet valid',
                    'valid_from' => $ticket->valid_from->toIso8601String(),
                ];
            }

            if ($ticket->valid_until && $ticket->valid_until->isPast()) {
                return [
                    'success' => false,
                    'message' => 'Ticket validity period has expired',
                    'expired_at' => $ticket->valid_until->toIso8601String(),
                ];
            }

            // Check if event has ended (only if end_date is set)
            if ($ticket->event->end_date && $ticket->event->end_date->isPast()) {
                return [
                    'success' => false,
                    'message' => 'This event has already ended',
                    'ended_at' => $ticket->event->end_date->toIso8601String(),
                ];
            }

            // Mark ticket as used
            $ticket->update([
                'status' => 'used',
                'used_at' => now(),
                'used_by' => $userId,
                'entry_gate' => $gateId,
                'entry_device' => $deviceId,
            ]);

            return [
                'success' => true,
                'message' => 'Ticket checked in successfully',
                'check_in_time' => now()->toIso8601String(),
                'ticket' => [
                    'id' => $ticket->id,
                    'code' => $ticket->ticket_code,
                    'holder_name' => $ticket->holder_name,
                    'type' => $ticket->ticket_type,
                    'seat' => $ticket->seat_number,
                ],
                'event' => [
                    'id' => $ticket->event->id,
                    'title' => $ticket->event->title,
                    'venue' => $ticket->event->venue_name,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Check-in error', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Check-in failed. Please try again.',
            ];
        }
    }
}
