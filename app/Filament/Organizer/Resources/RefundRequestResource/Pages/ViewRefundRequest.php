<?php

namespace App\Filament\Organizer\Resources\RefundRequestResource\Pages;

use App\Filament\Organizer\Resources\RefundRequestResource;
use App\Services\RefundService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewRefundRequest extends ViewRecord
{
    protected static string $resource = RefundRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve Refund')
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
                            ' | Original Payment: '.$this->record->currency.' '.number_format($this->record->booking->total_amount, 2)
                        )
                        ->rules([
                            fn (): \Closure => function (string $attribute, $value, \Closure $fail) {
                                if ($value > $this->record->booking->total_amount) {
                                    $fail('Refund amount cannot exceed the original payment amount.');
                                }
                            },
                        ]),
                    Forms\Components\Textarea::make('review_notes')
                        ->label('Internal Notes')
                        ->rows(3)
                        ->placeholder('Optional notes about this refund approval'),
                ])
                ->action(function (array $data): void {
                    DB::beginTransaction();
                    try {
                        // Update refund request
                        $this->record->approve(
                            admin: auth()->user(),
                            approvedAmount: $data['approved_amount'],
                            notes: $data['review_notes']
                        );

                        // Process the refund via Paystack
                        $refundService = app(RefundService::class);
                        $result = $refundService->processRefund($this->record);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Refund processed successfully')
                                ->body('Amount: '.$this->record->currency.' '.number_format($data['approved_amount'], 2))
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
                        ->rows(3)
                        ->placeholder('Explain why this refund is being rejected'),
                    Forms\Components\Textarea::make('customer_response')
                        ->label('Message to Customer')
                        ->rows(3)
                        ->placeholder('Optional message to send to the customer'),
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
}
