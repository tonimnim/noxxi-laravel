<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Event;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TicketController extends Controller
{
    use ApiResponse;

    /**
     * Get cancelled tickets for the authenticated organizer
     */
    public function cancelled(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->organizer) {
            return $this->forbidden('You must be an organizer to view cancelled tickets');
        }

        // Build query for cancelled tickets
        $query = Ticket::query()
            ->where('status', 'cancelled')
            ->whereHas('event', function ($q) use ($user) {
                $q->where('organizer_id', $user->organizer->id);
            })
            ->with([
                'event:id,title,event_date,venue_name',
                'booking:id,booking_code,user_id',
                'booking.user:id,full_name,email,phone_number'
            ]);

        // Apply filters using QueryBuilder
        $tickets = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('event_id'),
                AllowedFilter::exact('booking_id'),
                AllowedFilter::partial('ticket_code'),
                AllowedFilter::partial('holder_name'),
                AllowedFilter::exact('ticket_type'),
                AllowedFilter::callback('date_from', function ($query, $value) {
                    $query->whereDate('cancelled_at', '>=', $value);
                }),
                AllowedFilter::callback('date_to', function ($query, $value) {
                    $query->whereDate('cancelled_at', '<=', $value);
                }),
                AllowedFilter::callback('event_date_from', function ($query, $value) {
                    $query->whereHas('event', function ($q) use ($value) {
                        $q->whereDate('event_date', '>=', $value);
                    });
                }),
                AllowedFilter::callback('event_date_to', function ($query, $value) {
                    $query->whereHas('event', function ($q) use ($value) {
                        $q->whereDate('event_date', '<=', $value);
                    });
                }),
            ])
            ->allowedSorts([
                'cancelled_at',
                'created_at',
                'price',
                'holder_name',
                'ticket_type'
            ])
            ->defaultSort('-cancelled_at')
            ->paginate($request->per_page ?? 20);

        // Format response
        $ticketsData = collect($tickets->items())->map(function ($ticket) {
            return [
                'id' => $ticket->id,
                'ticket_code' => $ticket->ticket_code,
                'ticket_type' => $ticket->ticket_type,
                'price' => $ticket->price,
                'currency' => $ticket->currency,
                'holder' => [
                    'name' => $ticket->holder_name,
                    'email' => $ticket->holder_email,
                    'phone' => $ticket->holder_phone,
                ],
                'seat' => [
                    'number' => $ticket->seat_number,
                    'section' => $ticket->seat_section,
                ],
                'event' => [
                    'id' => $ticket->event->id,
                    'title' => $ticket->event->title,
                    'date' => $ticket->event->event_date->toIso8601String(),
                    'venue' => $ticket->event->venue_name,
                ],
                'booking' => [
                    'id' => $ticket->booking->id,
                    'code' => $ticket->booking->booking_code,
                    'customer' => [
                        'id' => $ticket->booking->user->id,
                        'name' => $ticket->booking->user->full_name,
                        'email' => $ticket->booking->user->email,
                        'phone' => $ticket->booking->user->phone_number,
                    ],
                ],
                'cancellation' => [
                    'cancelled_at' => $ticket->cancelled_at?->toIso8601String(),
                    'cancelled_by' => $ticket->cancelled_by,
                    'cancellation_reason' => $ticket->cancellation_reason,
                ],
                'created_at' => $ticket->created_at->toIso8601String(),
            ];
        });

        return $this->success([
            'tickets' => $ticketsData,
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    /**
     * Get cancellation statistics for the organizer
     */
    public function cancellationStats(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->organizer) {
            return $this->forbidden('You must be an organizer to view cancellation statistics');
        }

        $validated = $request->validate([
            'event_id' => 'nullable|uuid|exists:events,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'group_by' => 'nullable|in:day,week,month,event',
        ]);

        $query = Ticket::query()
            ->where('status', 'cancelled')
            ->whereHas('event', function ($q) use ($user) {
                $q->where('organizer_id', $user->organizer->id);
            });

        if ($validated['event_id'] ?? null) {
            $query->where('event_id', $validated['event_id']);
        }

        if ($validated['date_from'] ?? null) {
            $query->whereDate('cancelled_at', '>=', $validated['date_from']);
        }

        if ($validated['date_to'] ?? null) {
            $query->whereDate('cancelled_at', '<=', $validated['date_to']);
        }

        // Overall statistics
        $totalCancelled = (clone $query)->count();
        $totalRevenueLost = (clone $query)->sum('price');
        
        // Get cancellation reasons breakdown
        $reasonsBreakdown = (clone $query)
            ->select('cancellation_reason', DB::raw('COUNT(*) as count'), DB::raw('SUM(price) as revenue_lost'))
            ->groupBy('cancellation_reason')
            ->get()
            ->map(function ($item) {
                return [
                    'reason' => $item->cancellation_reason ?? 'No reason provided',
                    'count' => $item->count,
                    'revenue_lost' => $item->revenue_lost,
                ];
            });

        // Group by time period if requested
        $timeline = [];
        $groupBy = $validated['group_by'] ?? 'day';
        
        if (in_array($groupBy, ['day', 'week', 'month'])) {
            $format = match($groupBy) {
                'day' => 'Y-m-d',
                'week' => 'Y-W',
                'month' => 'Y-m',
            };

            $timeline = (clone $query)
                ->select(
                    DB::raw("DATE_FORMAT(cancelled_at, '{$format}') as period"),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(price) as revenue_lost')
                )
                ->groupBy('period')
                ->orderBy('period', 'desc')
                ->limit(30)
                ->get()
                ->map(function ($item) {
                    return [
                        'period' => $item->period,
                        'count' => $item->count,
                        'revenue_lost' => $item->revenue_lost,
                    ];
                });
        } elseif ($groupBy === 'event') {
            $timeline = (clone $query)
                ->select(
                    'event_id',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(price) as revenue_lost')
                )
                ->with('event:id,title,event_date')
                ->groupBy('event_id')
                ->orderBy('count', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($item) {
                    return [
                        'event' => [
                            'id' => $item->event->id,
                            'title' => $item->event->title,
                            'date' => $item->event->event_date->toIso8601String(),
                        ],
                        'count' => $item->count,
                        'revenue_lost' => $item->revenue_lost,
                    ];
                });
        }

        // Top cancelled events
        $topCancelledEvents = (clone $query)
            ->select('event_id', DB::raw('COUNT(*) as cancellation_count'))
            ->with('event:id,title')
            ->groupBy('event_id')
            ->orderBy('cancellation_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'event_id' => $item->event_id,
                    'event_title' => $item->event->title,
                    'cancellation_count' => $item->cancellation_count,
                ];
            });

        return $this->success([
            'summary' => [
                'total_cancelled' => $totalCancelled,
                'total_revenue_lost' => $totalRevenueLost,
                'average_cancellation_value' => $totalCancelled > 0 ? round($totalRevenueLost / $totalCancelled, 2) : 0,
            ],
            'reasons_breakdown' => $reasonsBreakdown,
            'timeline' => $timeline,
            'top_cancelled_events' => $topCancelledEvents,
            'filters_applied' => [
                'event_id' => $validated['event_id'] ?? null,
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
                'group_by' => $groupBy,
            ],
        ]);
    }

    /**
     * Bulk cancel tickets
     */
    public function bulkCancel(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->organizer) {
            return $this->forbidden('You must be an organizer to cancel tickets');
        }

        $validated = $request->validate([
            'ticket_ids' => 'required|array|min:1',
            'ticket_ids.*' => 'uuid|exists:tickets,id',
            'reason' => 'required|string|max:500',
        ]);

        // Verify ownership of all tickets
        $tickets = Ticket::whereIn('id', $validated['ticket_ids'])
            ->whereHas('event', function ($q) use ($user) {
                $q->where('organizer_id', $user->organizer->id);
            })
            ->where('status', '!=', 'cancelled')
            ->get();

        if ($tickets->count() !== count($validated['ticket_ids'])) {
            return $this->error('Some tickets could not be found or are already cancelled');
        }

        // Cancel tickets
        $cancelledCount = 0;
        DB::transaction(function () use ($tickets, $validated, $user, &$cancelledCount) {
            foreach ($tickets as $ticket) {
                $ticket->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancelled_by' => $user->id,
                    'cancellation_reason' => $validated['reason'],
                ]);
                $cancelledCount++;
            }
        });

        return $this->success([
            'cancelled_count' => $cancelledCount,
            'message' => "{$cancelledCount} tickets have been cancelled successfully",
        ]);
    }
}