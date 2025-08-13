<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Ticket;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QrCodeService
{
    /**
     * Generate QR code for a ticket with security signature
     */
    public function generateTicketQrCode(Ticket $ticket): array
    {
        $event = $ticket->event;
        
        // Generate QR data payload
        $qrData = [
            'tid' => $ticket->id,
            'eid' => $event->id,
            'code' => $ticket->ticket_code,
            'type' => $ticket->ticket_type,
            'exp' => $event->end_date?->timestamp ?? null,
            'iat' => now()->timestamp,
        ];
        
        // Generate HMAC signature for security
        $signature = $this->generateSignature($qrData, $event->qr_secret_key);
        
        // Combine data and signature
        $qrContent = base64_encode(json_encode($qrData) . '|' . $signature);
        
        // Generate QR code image
        $qrCode = new QrCode($qrContent);
        $qrCode->setSize(400);
        $qrCode->setMargin(10);
        $qrCode->setEncoding(new Encoding('UTF-8'));
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::High);
        
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        // Save QR code image
        $filename = 'tickets/qr/' . $ticket->id . '.png';
        Storage::disk('public')->put($filename, $result->getString());
        
        // Generate ticket hash for offline validation
        $ticketHash = $this->generateTicketHash($ticket, $event);
        
        // Update ticket with QR data
        $ticket->update([
            'qr_code' => Storage::url($filename),
            'ticket_hash' => $ticketHash,
            'offline_validation_data' => [
                'event_id' => $event->id,
                'event_name' => $event->title,
                'venue' => $event->venue_name,
                'date' => $event->event_date->toIso8601String(),
                'ticket_type' => $ticket->ticket_type,
                'holder_name' => $ticket->holder_name,
                'valid_gates' => $event->gates_config['gates'] ?? ['main'],
            ]
        ]);
        
        return [
            'qr_code_url' => Storage::url($filename),
            'qr_content' => $qrContent,
            'ticket_hash' => $ticketHash,
        ];
    }
    
    /**
     * Validate a scanned QR code
     */
    public function validateQrCode(string $qrContent, ?string $gateId = null): array
    {
        try {
            // Decode QR content
            $decoded = base64_decode($qrContent);
            if (!$decoded) {
                return $this->validationResponse(false, 'Invalid QR code format');
            }
            
            // Split data and signature
            $parts = explode('|', $decoded);
            if (count($parts) !== 2) {
                return $this->validationResponse(false, 'Invalid QR code structure');
            }
            
            [$dataJson, $providedSignature] = $parts;
            $qrData = json_decode($dataJson, true);
            
            if (!$qrData) {
                return $this->validationResponse(false, 'Invalid QR data');
            }
            
            // Find ticket and event
            $ticket = Ticket::find($qrData['tid'] ?? null);
            if (!$ticket) {
                return $this->validationResponse(false, 'Ticket not found');
            }
            
            $event = $ticket->event;
            if (!$event) {
                return $this->validationResponse(false, 'Event not found');
            }
            
            // Verify signature
            $expectedSignature = $this->generateSignature($qrData, $event->qr_secret_key);
            if (!hash_equals($expectedSignature, $providedSignature)) {
                return $this->validationResponse(false, 'Invalid ticket signature', [
                    'fraud_alert' => true,
                    'ticket_id' => $ticket->id,
                ]);
            }
            
            // Check if ticket is already used
            if ($ticket->status === 'used') {
                return $this->validationResponse(false, 'Ticket already used', [
                    'used_at' => $ticket->used_at?->toIso8601String(),
                    'used_by' => $ticket->usedBy?->full_name,
                    'entry_gate' => $ticket->entry_gate,
                ]);
            }
            
            // Check if ticket is valid
            if (!$ticket->isValid()) {
                return $this->validationResponse(false, 'Ticket is not valid', [
                    'status' => $ticket->status,
                ]);
            }
            
            // Check gate access if provided
            if ($gateId) {
                $allowedGates = $event->gates_config['gates'] ?? ['main'];
                $vipGates = $event->gates_config['vip_gates'] ?? [];
                
                if (!in_array($gateId, $allowedGates) && !in_array($gateId, $vipGates)) {
                    return $this->validationResponse(false, 'Invalid gate for this event');
                }
                
                // Check if ticket type has access to this gate
                if (in_array($gateId, $vipGates) && !str_contains(strtolower($ticket->ticket_type), 'vip')) {
                    return $this->validationResponse(false, 'This ticket does not have access to VIP gate');
                }
            }
            
            // Check event timing
            $now = now();
            if ($event->event_date->subHours(2)->gt($now)) {
                return $this->validationResponse(false, 'Event has not started yet', [
                    'event_starts' => $event->event_date->toIso8601String(),
                ]);
            }
            
            if ($event->end_date && $event->end_date->lt($now)) {
                return $this->validationResponse(false, 'Event has ended');
            }
            
            return $this->validationResponse(true, 'Ticket is valid', [
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
                    'id' => $event->id,
                    'title' => $event->title,
                    'venue' => $event->venue_name,
                    'date' => $event->event_date->toIso8601String(),
                ],
                'can_check_in' => true,
            ]);
            
        } catch (\Exception $e) {
            return $this->validationResponse(false, 'Validation error: ' . $e->getMessage());
        }
    }
    
    /**
     * Check in a ticket (mark as used)
     */
    public function checkInTicket(string $ticketId, string $userId, ?string $gateId = null, ?string $deviceId = null): array
    {
        $ticket = Ticket::find($ticketId);
        
        if (!$ticket) {
            return ['success' => false, 'message' => 'Ticket not found'];
        }
        
        if ($ticket->status === 'used') {
            return [
                'success' => false,
                'message' => 'Ticket already checked in',
                'used_at' => $ticket->used_at?->toIso8601String(),
            ];
        }
        
        // Mark ticket as used
        $ticket->markAsUsed($userId, $gateId, $deviceId);
        
        // Update event statistics
        $event = $ticket->event;
        $event->increment('tickets_scanned');
        
        return [
            'success' => true,
            'message' => 'Check-in successful',
            'ticket' => [
                'id' => $ticket->id,
                'holder_name' => $ticket->holder_name,
                'type' => $ticket->ticket_type,
            ],
        ];
    }
    
    /**
     * Generate offline validation manifest for an event
     */
    public function generateOfflineManifest(Event $event): array
    {
        $tickets = Ticket::where('event_id', $event->id)
            ->whereIn('status', ['valid', 'transferred'])
            ->get(['id', 'ticket_code', 'ticket_hash', 'ticket_type', 'holder_name', 'status']);
        
        $manifest = [
            'event_id' => $event->id,
            'event_title' => $event->title,
            'venue' => $event->venue_name,
            'date' => $event->event_date->toIso8601String(),
            'generated_at' => now()->toIso8601String(),
            'total_tickets' => $tickets->count(),
            'gates' => $event->gates_config ?? ['gates' => ['main']],
            'tickets' => $tickets->map(function ($ticket) {
                return [
                    'h' => $ticket->ticket_hash, // hash for validation
                    't' => substr($ticket->ticket_type, 0, 3), // type abbreviated
                    'n' => $ticket->holder_name,
                    's' => $ticket->status === 'valid' ? 1 : 2, // 1=valid, 2=transferred
                ];
            })->toArray(),
        ];
        
        // Compress and encode for efficient transfer
        $compressed = gzcompress(json_encode($manifest), 9);
        
        return [
            'manifest' => base64_encode($compressed),
            'checksum' => hash('sha256', $compressed),
            'size' => strlen($compressed),
            'ticket_count' => $tickets->count(),
        ];
    }
    
    /**
     * Generate HMAC signature
     */
    private function generateSignature(array $data, string $secretKey): string
    {
        ksort($data); // Ensure consistent ordering
        return hash_hmac('sha256', json_encode($data), $secretKey);
    }
    
    /**
     * Generate ticket hash for offline validation
     */
    private function generateTicketHash(Ticket $ticket, Event $event): string
    {
        $data = $ticket->id . $ticket->ticket_code . $event->id . $event->qr_secret_key;
        return substr(hash('sha256', $data), 0, 16); // Short hash for offline storage
    }
    
    /**
     * Format validation response
     */
    private function validationResponse(bool $success, string $message, array $data = []): array
    {
        return array_merge([
            'success' => $success,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ], $data);
    }
}