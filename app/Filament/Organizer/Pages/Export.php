<?php

namespace App\Filament\Organizer\Pages;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class Export extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Export Data';

    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.organizer.pages.export';

    public string $activeTab = 'revenue';
    public ?array $revenueData = [];
    public ?array $bookingData = [];

    public function mount(): void
    {
        $this->revenueData = [
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ];

        $this->bookingData = [
            'export_type' => 'all',
            'event_id' => null,
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ];
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function revenueForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From')
                            ->required()
                            ->maxDate(now()),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('To')
                            ->required()
                            ->maxDate(now())
                            ->afterOrEqual('date_from'),
                    ])
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                    ]),
            ])
            ->statePath('revenueData');
    }

    public function bookingForm(Form $form): Form
    {
        $organizer = Auth::user()->organizer;
        
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Select::make('export_type')
                            ->label('Type')
                            ->options([
                                'all' => 'All Bookings',
                                'by_event' => 'By Event',
                            ])
                            ->reactive()
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                            ]),
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From')
                            ->required()
                            ->maxDate(now())
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                            ]),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('To')
                            ->required()
                            ->maxDate(now())
                            ->afterOrEqual('date_from')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                            ]),
                    ])
                    ->columns([
                        'default' => 1,
                        'sm' => 3,
                    ]),
                Forms\Components\Select::make('event_id')
                    ->label('Select Event')
                    ->options(function () use ($organizer) {
                        return Event::where('organizer_id', $organizer->id)
                            ->orderBy('event_date', 'desc')
                            ->pluck('title', 'id');
                    })
                    ->searchable()
                    ->placeholder('Choose an event')
                    ->visible(fn ($get) => $get('export_type') === 'by_event')
                    ->required(fn ($get) => $get('export_type') === 'by_event'),
            ])
            ->statePath('bookingData');
    }

    public function exportRevenue(): void
    {
        $organizer = Auth::user()->organizer;
        
        if (!$organizer) {
            Notification::make()
                ->title('Error')
                ->body('No organizer account found.')
                ->danger()
                ->send();
            return;
        }

        $dateFrom = Carbon::parse($this->revenueData['date_from'])->startOfDay();
        $dateTo = Carbon::parse($this->revenueData['date_to'])->endOfDay();

        // Get transactions for the organizer
        $transactions = Transaction::where('organizer_id', $organizer->id)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->where('type', Transaction::TYPE_TICKET_SALE)
            ->whereBetween('processed_at', [$dateFrom, $dateTo])
            ->with(['booking.event', 'booking.user'])
            ->orderBy('processed_at', 'desc')
            ->get();

        if ($transactions->isEmpty()) {
            Notification::make()
                ->title('No Data')
                ->body('No revenue transactions found for the selected period.')
                ->warning()
                ->send();
            return;
        }

        // Prepare CSV data
        $csvData = [];
        $csvData[] = ['Date', 'Event', 'Customer', 'Booking Ref', 'Gross Amount', 'Commission', 'Processing Fee', 'Net Revenue', 'Currency'];

        foreach ($transactions as $transaction) {
            $csvData[] = [
                $transaction->processed_at->format('Y-m-d H:i'),
                $transaction->booking->event->title ?? 'N/A',
                $transaction->booking->user->full_name ?? 'N/A',
                $transaction->booking->booking_reference,
                number_format($transaction->amount, 2),
                number_format($transaction->commission_amount, 2),
                number_format($transaction->gateway_fee, 2),
                number_format($transaction->net_amount, 2),
                $transaction->currency,
            ];
        }

        // Add summary row
        $csvData[] = [];
        $csvData[] = [
            'TOTAL',
            '',
            '',
            '',
            number_format($transactions->sum('amount'), 2),
            number_format($transactions->sum('commission_amount'), 2),
            number_format($transactions->sum('gateway_fee'), 2),
            number_format($transactions->sum('net_amount'), 2),
            $organizer->default_currency ?? 'KES',
        ];

        // Generate CSV
        $filename = 'revenue_export_' . now()->format('Y-m-d_His') . '.csv';
        $this->downloadCsv($csvData, $filename);

        Notification::make()
            ->title('Export Successful')
            ->body('Revenue data has been exported successfully.')
            ->success()
            ->send();
    }

    public function exportBookings(): void
    {
        $organizer = Auth::user()->organizer;
        
        if (!$organizer) {
            Notification::make()
                ->title('Error')
                ->body('No organizer account found.')
                ->danger()
                ->send();
            return;
        }

        $dateFrom = Carbon::parse($this->bookingData['date_from'])->startOfDay();
        $dateTo = Carbon::parse($this->bookingData['date_to'])->endOfDay();

        // Build query
        $query = Booking::whereHas('event', function ($q) use ($organizer) {
            $q->where('organizer_id', $organizer->id);
        })
        ->whereBetween('created_at', [$dateFrom, $dateTo])
        ->with(['event', 'user', 'tickets']);

        // Filter by specific event if selected
        if ($this->bookingData['export_type'] === 'by_event' && $this->bookingData['event_id']) {
            $query->where('event_id', $this->bookingData['event_id']);
        }

        $bookings = $query->orderBy('created_at', 'desc')->get();

        if ($bookings->isEmpty()) {
            Notification::make()
                ->title('No Data')
                ->body('No bookings found for the selected criteria.')
                ->warning()
                ->send();
            return;
        }

        // Prepare CSV data
        $csvData = [];
        $csvData[] = ['Booking Date', 'Booking Reference', 'Event', 'Customer Name', 'Email', 'Phone', 'Tickets', 'Total Amount', 'Payment Status', 'Currency'];

        foreach ($bookings as $booking) {
            $csvData[] = [
                $booking->created_at->format('Y-m-d H:i'),
                $booking->booking_reference,
                $booking->event->title,
                $booking->user->full_name ?? 'N/A',
                $booking->user->email ?? 'N/A',
                $booking->user->phone_number ?? 'N/A',
                $booking->ticket_quantity,
                number_format($booking->total_amount, 2),
                ucfirst($booking->payment_status),
                $booking->currency,
            ];
        }

        // Add summary row
        $csvData[] = [];
        $csvData[] = [
            'TOTAL',
            '',
            '',
            '',
            '',
            '',
            $bookings->sum('ticket_quantity'),
            number_format($bookings->sum('total_amount'), 2),
            '',
            $organizer->default_currency ?? 'KES',
        ];

        // Generate CSV
        $filename = 'bookings_export_' . now()->format('Y-m-d_His') . '.csv';
        $this->downloadCsv($csvData, $filename);

        Notification::make()
            ->title('Export Successful')
            ->body('Booking data has been exported successfully.')
            ->success()
            ->send();
    }

    private function downloadCsv(array $data, string $filename): void
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        Response::stream($callback, 200, $headers)->send();
        exit;
    }

    protected function getForms(): array
    {
        return [
            'revenueForm',
            'bookingForm',
        ];
    }
}