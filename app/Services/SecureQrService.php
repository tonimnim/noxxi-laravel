<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Event;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
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
     * @param Ticket $ticket
     * @param bool $verifyOwnership
     * @return array
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
                    'owner_id' => $ticket->assigned_to
                ]);
                throw new \Exception('Unauthorized access to ticket QR code');
            }
        }

        // Load event if not loaded
        if (!$ticket->relationLoaded('event')) {
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
            'image_base64' => 'data:image/png;base64,' . base64_encode($qrImage),
            'expires_at' => $qrData['expires_at'],
            'signature' => $qrData['signature']
        ];
    }

    /**
     * Generate secure QR data with time-limited signature
     */
    private function generateSecureQrData(Ticket $ticket): array
    {
        $expiresAt = now()->addHours(24); // QR valid for 24 hours
        
        $data = [
            'tid' => $ticket->id,
            'eid' => $ticket->event_id,
            'code' => $ticket->ticket_code,
            'type' => $ticket->ticket_type,
            'exp' => $expiresAt->timestamp,
            'iat' => now()->timestamp,
            'nonce' => bin2hex(random_bytes(8)) // Prevent replay attacks
        ];
        
        // Generate secure signature
        $signature = $this->generateSignature($data, $ticket->event);
        
        // Encode for QR
        $encoded = base64_encode(json_encode([
            'data' => $data,
            'sig' => $signature
        ]));
        
        return [
            'encoded' => $encoded,
            'signature' => $signature,
            'expires_at' => $expiresAt->toIso8601String()
        ];
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
            
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            return $result->getString();
        } catch (\Exception $e) {
            \Log::error('QR image generation failed', [
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Unable to generate QR image: ' . $e->getMessage());
        }
    }

    /**
     * Validate QR code data and signature
     */
    public function validateQrCode(string $qrContent): array
    {
        try {
            $decoded = json_decode(base64_decode($qrContent), true);
            
            if (!$decoded || !isset($decoded['data']) || !isset($decoded['sig'])) {
                return ['valid' => false, 'error' => 'Invalid QR format'];
            }
            
            $data = $decoded['data'];
            $signature = $decoded['sig'];
            
            // Check expiry
            if (isset($data['exp']) && $data['exp'] < time()) {
                return ['valid' => false, 'error' => 'QR code expired'];
            }
            
            // Load ticket and event for signature verification
            $ticket = Ticket::with('event')->find($data['tid']);
            if (!$ticket) {
                return ['valid' => false, 'error' => 'Ticket not found'];
            }
            
            // Verify signature
            $expectedSignature = $this->generateSignature($data, $ticket->event);
            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('QR signature mismatch', [
                    'ticket_id' => $data['tid'],
                    'provided_sig' => $signature
                ]);
                return ['valid' => false, 'error' => 'Invalid signature'];
            }
            
            // Check if ticket is already used
            if ($ticket->status === 'used') {
                return ['valid' => false, 'error' => 'Ticket already used', 'used_at' => $ticket->used_at];
            }
            
            return [
                'valid' => true,
                'ticket' => $ticket,
                'data' => $data
            ];
            
        } catch (\Exception $e) {
            Log::error('QR validation error', ['error' => $e->getMessage()]);
            return ['valid' => false, 'error' => 'Validation failed'];
        }
    }

    /**
     * Log QR generation for audit trail
     */
    private function logQrGeneration(Ticket $ticket): void
    {
        // Log to cache for rate limiting
        $key = 'qr_generated_' . $ticket->id . '_' . (Auth::id() ?? 'anonymous');
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, 3600); // Track for 1 hour
        
        // Log to file for audit
        Log::info('QR code generated', [
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * Check if user has exceeded QR generation rate limit
     */
    public function checkRateLimit(Ticket $ticket): bool
    {
        $key = 'qr_generated_' . $ticket->id . '_' . (Auth::id() ?? 'anonymous');
        $attempts = Cache::get($key, 0);
        
        // Allow 10 QR generations per hour per ticket
        return $attempts < 10;
    }
}