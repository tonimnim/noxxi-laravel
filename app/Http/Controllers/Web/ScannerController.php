<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use App\Services\ScannerService;
use App\Services\SecureQrService;
use App\Traits\HasScannerPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScannerController extends Controller
{
    use HasScannerPermissions;

    protected SecureQrService $qrService;

    protected ScannerService $scannerService;

    public function __construct(SecureQrService $qrService, ScannerService $scannerService)
    {
        $this->qrService = $qrService;
        $this->scannerService = $scannerService;
    }

    /**
     * Display the scanner interface
     */
    public function index()
    {
        $user = Auth::user();

        // Get scanner context from middleware
        $scannerContext = request()->input('scanner_context');

        // Get allowed events for this user
        $allowedEvents = $this->getAllowedEvents($user);

        return view('scanner.index', [
            'scannerContext' => $scannerContext,
            'allowedEvents' => $allowedEvents,
            'csrfToken' => csrf_token(),
        ]);
    }

    /**
     * Validate a ticket from QR code
     * SECURITY: Double-check permissions even after middleware
     */
    public function validateTicket(Request $request)
    {
        $validated = $request->validate([
            'qr_content' => 'required|string|max:10000',
            'event_id' => 'nullable|uuid',
        ]);

        // Log validation attempt for audit trail
        Log::info('Web scanner validation attempt', [
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'has_qr' => ! empty($validated['qr_content']),
        ]);

        // Validate QR code using service
        $result = $this->qrService->validateQrCode(
            $validated['qr_content']
        );

        if ($result['success']) {
            // SECURITY: Verify user has permission for this specific ticket's event
            $ticket = Ticket::find($result['ticket']['id']);
            if ($ticket && ! $this->userCanScanTicket($ticket)) {
                Log::warning('Unauthorized web scanner validation attempt', [
                    'user_id' => Auth::id(),
                    'ticket_id' => $ticket->id,
                    'event_id' => $ticket->event_id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to scan this ticket',
                ], 403);
            }

            // Add scanner info to result
            $result['scanner'] = [
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'scanned_at' => now()->toIso8601String(),
                'method' => 'web',
            ];
        }

        return response()->json($result);
    }

    /**
     * Check in a ticket (mark as used)
     * SECURITY: Comprehensive permission and validity checks
     */
    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => 'required|uuid',
            'event_id' => 'required|uuid',
            'force' => 'boolean',
        ]);

        // Get ticket with relationships
        $ticket = Ticket::with(['event', 'booking'])->find($validated['ticket_id']);

        if (! $ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
            ], 404);
        }

        // SECURITY: Verify permissions
        if (! $this->userCanScanTicket($ticket)) {
            Log::alert('Unauthorized check-in attempt', [
                'user_id' => Auth::id(),
                'ticket_id' => $ticket->id,
                'event_id' => $ticket->event_id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to check in this ticket',
            ], 403);
        }

        // Delegate check-in logic to service
        $result = $this->scannerService->checkInTicket($ticket, $validated);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get event manifest for offline scanning
     */
    public function getManifest($eventId)
    {
        $event = Event::find($eventId);

        if (! $event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        // Verify user has permission for this event
        if (! $this->userCanManageEvent($event)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this manifest',
            ], 403);
        }

        // Cache manifest for performance (shorter cache time for real-time accuracy)
        $cacheKey = 'event_manifest_signed_'.$eventId;
        $signedManifest = Cache::remember($cacheKey, 60, function () use ($event) {
            // Generate the manifest with signatures
            $manifest = $this->qrService->generateOfflineManifest($event);

            // Sign the entire manifest to prevent tampering
            $manifestSignature = $this->qrService->generateManifestSignature($manifest);

            return [
                'tickets' => $manifest,
                'signature' => $manifestSignature,
                'generated_at' => now()->toIso8601String(),
                'expires_at' => now()->addHours(24)->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'venue' => $event->venue_name,
                'date' => $event->event_date?->toIso8601String(),
                'listing_type' => $event->listing_type,
            ],
            'manifest' => $signedManifest,
        ]);
    }

    /**
     * Get real-time check-in statistics
     */
    public function getStats($eventId)
    {
        $event = Event::find($eventId);

        if (! $event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        // Verify user has permission for this event
        if (! $this->userCanManageEvent($event)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view these statistics',
            ], 403);
        }

        // Get statistics
        $stats = DB::table('tickets')
            ->where('event_id', $eventId)
            ->selectRaw("
                COUNT(*) as total_tickets,
                COUNT(CASE WHEN status = 'used' THEN 1 END) as checked_in,
                COUNT(CASE WHEN status = 'valid' THEN 1 END) as not_checked_in,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled
            ")
            ->first();

        // Get recent check-ins
        $recentCheckIns = Ticket::where('event_id', $eventId)
            ->where('status', 'used')
            ->orderBy('used_at', 'desc')
            ->limit(5)
            ->get(['id', 'holder_name', 'ticket_type', 'used_at']);

        // Get check-ins by hour for chart
        $hourlyCheckIns = DB::table('tickets')
            ->where('event_id', $eventId)
            ->where('status', 'used')
            ->whereNotNull('used_at')
            ->selectRaw("DATE_TRUNC('hour', used_at) as hour, COUNT(*) as count")
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return response()->json([
            'success' => true,
            'stats' => [
                'total' => $stats->total_tickets,
                'checked_in' => $stats->checked_in,
                'remaining' => $stats->not_checked_in,
                'cancelled' => $stats->cancelled,
                'percentage' => $stats->total_tickets > 0
                    ? round(($stats->checked_in / $stats->total_tickets) * 100, 1)
                    : 0,
            ],
            'recent_check_ins' => $recentCheckIns,
            'hourly_data' => $hourlyCheckIns,
            'last_updated' => now()->toIso8601String(),
        ]);
    }
}
