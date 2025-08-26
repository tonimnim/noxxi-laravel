<?php

namespace App\Http\Controllers\Web;

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
        // Middleware is handled in routes file
    }

    /**
     * Generate QR code for a specific ticket (session auth)
     */
    public function generateQr(Request $request, string $ticketId): JsonResponse
    {
        // Rate limiting per user
        $key = 'qr-generation-web:' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 20)) { // More lenient for web
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'status' => 'error',
                'message' => 'Too many requests. Please wait ' . $seconds . ' seconds.'
            ], 429);
        }
        RateLimiter::hit($key, 60);

        // Find ticket and verify ownership
        $ticket = Ticket::with('event')
            ->where('id', $ticketId)
            ->where('assigned_to', Auth::id())
            ->first();

        if (!$ticket) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket not found or access denied'
            ], 404);
        }

        // Check if ticket is valid
        if ($ticket->status !== 'valid') {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket is not valid. Status: ' . $ticket->status
            ], 400);
        }

        try {
            // Generate QR code securely
            $qrData = $this->qrService->generateSecureQrCode($ticket, true);
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'qr_image' => $qrData['image_base64'],
                    'expires_at' => $qrData['expires_at'],
                    'ticket' => [
                        'id' => $ticket->id,
                        'code' => $ticket->ticket_code,
                        'type' => $ticket->ticket_type,
                        'event' => [
                            'title' => $ticket->event->title,
                            'venue' => $ticket->event->venue_name,
                            'date' => $ticket->event->event_date->toIso8601String()
                        ]
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('QR generation failed', [
                'ticket_id' => $ticketId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate QR code. Please try again.'
            ], 500);
        }
    }
}