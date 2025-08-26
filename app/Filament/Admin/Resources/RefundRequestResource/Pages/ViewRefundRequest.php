<?php

namespace App\Filament\Admin\Resources\RefundRequestResource\Pages;

use App\Filament\Admin\Resources\RefundRequestResource;
use App\Services\RefundService;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewRefundRequest extends ViewRecord
{
    protected static string $resource = RefundRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('process')
                ->label('Process Refund')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => in_array($this->record->status, ['pending', 'reviewing'])
                )
                ->requiresConfirmation()
                ->form([
                    Forms\Components\TextInput::make('approved_amount')
                        ->label('Refund Amount')
                        ->numeric()
                        ->required()
                        ->prefix($this->record->currency)
                        ->default($this->record->requested_amount)
                        ->helperText(
                            'Requested: '.$this->record->currency.' '.number_format($this->record->requested_amount, 2).
                            ' | Original: '.$this->record->currency.' '.number_format($this->record->booking->total_amount, 2)
                        ),
                    Forms\Components\Textarea::make('review_notes')
                        ->label('Internal Notes')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    DB::beginTransaction();
                    try {
                        $this->record->approve(
                            admin: auth()->user(),
                            approvedAmount: $data['approved_amount'],
                            notes: $data['review_notes']
                        );

                        $refundService = app(RefundService::class);
                        $result = $refundService->processRefund($this->record);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Refund processed successfully')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception($result['message']);
                        }

                        DB::commit();
                        $this->redirect($this->getResource()::getUrl('index'));
                    } catch (\Exception $e) {
                        DB::rollback();
                        Notification::make()
                            ->title('Refund processing failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => in_array($this->record->status, ['pending', 'reviewing'])
                )
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->required()
                        ->rows(3),
                    Forms\Components\Textarea::make('customer_response')
                        ->label('Message to Customer')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->record->reject(
                        admin: auth()->user(),
                        reason: $data['rejection_reason'],
                        customerResponse: $data['customer_response']
                    );

                    Notification::make()
                        ->title('Refund request rejected')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Refund Request Details')
                    ->schema([
                        Components\TextEntry::make('booking.booking_reference')
                            ->label('Booking Reference')
                            ->copyable(),
                        Components\TextEntry::make('booking.event.title')
                            ->label('Event'),
                        Components\TextEntry::make('booking.event.organizer.business_name')
                            ->label('Organizer'),
                        Components\TextEntry::make('user.full_name')
                            ->label('Customer'),
                        Components\TextEntry::make('user.email')
                            ->label('Customer Email')
                            ->copyable(),
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'reviewing' => 'info',
                                'approved' => 'success',
                                'processed' => 'success',
                                'rejected' => 'danger',
                                'cancelled' => 'gray',
                                default => 'gray',
                            }),
                    ])
                    ->columns(3),

                Components\Section::make('Financial Information')
                    ->schema([
                        Components\TextEntry::make('booking.total_amount')
                            ->label('Original Payment')
                            ->money($this->record->currency),
                        Components\TextEntry::make('requested_amount')
                            ->label('Requested Amount')
                            ->money($this->record->currency),
                        Components\TextEntry::make('approved_amount')
                            ->label('Approved Amount')
                            ->money($this->record->currency)
                            ->placeholder('Not yet approved'),
                        Components\TextEntry::make('reason')
                            ->label('Refund Reason')
                            ->columnSpan(3),
                    ])
                    ->columns(3),

                Components\Section::make('Audit Trail')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label('Requested At')
                            ->dateTime(),
                        Components\TextEntry::make('reviewedBy.full_name')
                            ->label('Reviewed By')
                            ->placeholder('Not reviewed'),
                        Components\TextEntry::make('reviewed_at')
                            ->label('Reviewed At')
                            ->dateTime()
                            ->placeholder('Not reviewed'),
                        Components\TextEntry::make('processedBy.full_name')
                            ->label('Processed By')
                            ->placeholder('Not processed'),
                        Components\TextEntry::make('processed_at')
                            ->label('Processed At')
                            ->dateTime()
                            ->placeholder('Not processed'),
                        Components\TextEntry::make('transaction.gateway_reference')
                            ->label('Transaction Reference')
                            ->copyable()
                            ->placeholder('No transaction'),
                    ])
                    ->columns(3),

                Components\Section::make('Notes')
                    ->schema([
                        Components\TextEntry::make('customer_message')
                            ->label('Customer Message')
                            ->placeholder('No message from customer'),
                        Components\TextEntry::make('review_notes')
                            ->label('Internal Notes')
                            ->placeholder('No internal notes'),
                        Components\TextEntry::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->placeholder('Not rejected'),
                        Components\TextEntry::make('admin_response')
                            ->label('Response to Customer')
                            ->placeholder('No response sent'),
                    ])
                    ->columns(2),
            ]);
    }
}
