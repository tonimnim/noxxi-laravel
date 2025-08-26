<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\SecureQrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class SecureQrController extends Controller
{
    protected SecureQrService $qrService;

    public function __construct(SecureQrService $qrService)
    {
        $this->qrService = $qrService;
    }

    /**
     * Generate QR code for a specific ticket
     * 
     * @authenticated
     * @rateLimit 10 per minute
     */
    public function generateQr(Request $request, string $ticketId): JsonResponse
    {
        // Rate limiting per user
        $key = 'qr-generation:' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'error' => 'Too many QR generation attempts. Please try again in ' . $seconds . ' seconds.'
            ], 429);
        }
        RateLimiter::hit($key, 60); // 10 attempts per minute

        // Find ticket and verify ownership
        $ticket = Ticket::where('id', $ticketId)
            ->where('assigned_to', Auth::id())
            ->first();

        if (!$ticket) {
            return response()->json([
                'error' => 'Ticket not found or you do not have access to this ticket'
            ], 404);
        }

        // Check ticket-specific rate limit
        if (!$this->qrService->checkRateLimit($ticket)) {
            return response()->json([
                'error' => 'QR code generation limit exceeded for this ticket. Please try again later.'
            ], 429);
        }

        try {
            // Generate QR code securely (in memory only)
            $qrData = $this->qrService->generateSecureQrCode($ticket, true);
            
            return response()->json([
                'success' => true,
                'qr' => [
                    'image' => $qrData['image_base64'],
                    'expires_at' => $qrData['expires_at'],
                    'ticket_code' => $ticket->ticket_code
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Validate a scanned QR code
     * 
     * @authenticated
     * @requiresRole organizer,scanner
     */
    public function validateQr(Request $request): JsonResponse
    {
        $request->validate([
            'qr_data' => 'required|string'
        ]);

        // Only organizers and scanners can validate QR codes
        if (!in_array(Auth::user()->role, ['organizer', 'admin'])) {
            return response()->json([
                'error' => 'Unauthorized to validate tickets'
            ], 403);
        }

        $result = $this->qrService->validateQrCode($request->qr_data);
        
        if (!$result['valid']) {
            return response()->json([
                'valid' => false,
                'error' => $result['error']
            ], 400);
        }

        // Mark ticket as used if validation successful
        $ticket = $result['ticket'];
        if ($request->boolean('mark_used')) {
            $ticket->update([
                'status' => 'used',
                'used_at' => now(),
                'scanned_by' => Auth::id()
            ]);
        }

        return response()->json([
            'valid' => true,
            'ticket' => [
                'id' => $ticket->id,
                'code' => $ticket->ticket_code,
                'type' => $ticket->ticket_type,
                'holder_name' => $ticket->holder_name,
                'event' => [
                    'title' => $ticket->event->title,
                    'venue' => $ticket->event->venue_name
                ]
            ]
        ]);
    }

    /**
     * Get QR code for multiple tickets (batch)
     * 
     * @authenticated
     * @rateLimit 5 per minute
     */
    public function batchGenerateQr(Request $request): JsonResponse
    {
        $request->validate([
            'ticket_ids' => 'required|array|max:10',
            'ticket_ids.*' => 'uuid'
        ]);

        // Rate limiting for batch operations
        $key = 'qr-batch:' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'error' => 'Too many batch requests. Please try again in ' . $seconds . ' seconds.'
            ], 429);
        }
        RateLimiter::hit($key, 60);

        // Find tickets and verify ownership
        $tickets = Ticket::whereIn('id', $request->ticket_ids)
            ->where('assigned_to', Auth::id())
            ->get();

        if ($tickets->count() !== count($request->ticket_ids)) {
            return response()->json([
                'error' => 'Some tickets not found or you do not have access'
            ], 404);
        }

        $qrCodes = [];
        foreach ($tickets as $ticket) {
            try {
                $qrData = $this->qrService->generateSecureQrCode($ticket, true);
                $qrCodes[$ticket->id] = [
                    'image' => $qrData['image_base64'],
                    'expires_at' => $qrData['expires_at'],
                    'ticket_code' => $ticket->ticket_code
                ];
            } catch (\Exception $e) {
                $qrCodes[$ticket->id] = [
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'qr_codes' => $qrCodes
        ]);
    }
}