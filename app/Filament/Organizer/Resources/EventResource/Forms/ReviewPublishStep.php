<?php

namespace App\Filament\Organizer\Resources\EventResource\Forms;

use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\HtmlString;

class ReviewPublishStep
{
    public static function make(): Wizard\Step
    {
        return Wizard\Step::make('Review & Publish')
            ->description('Review your listing before publishing')
            ->icon('heroicon-o-check-circle')
            ->schema([
                Forms\Components\Section::make('Review Your Listing')
                    ->description('Please review all details before publishing')
                    ->schema([
                        Forms\Components\Placeholder::make('review_summary')
                            ->content(function ($get) {
                                return static::generateReviewSummary($get);
                            })
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Publishing Options')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Listing Status')
                            ->options([
                                'draft' => 'Save as Draft',
                                'published' => 'Publish Now',
                            ])
                            ->default('draft')
                            ->required()
                            ->helperText('You can save as draft and publish later'),
                            
                        Forms\Components\Checkbox::make('agree_terms')
                            ->label('I confirm all information is accurate and I agree to the platform terms')
                            ->required()
                            ->accepted()
                            ->validationMessages([
                                'accepted' => 'You must confirm the information and agree to terms.',
                            ]),
                    ])
                    ->columns(1),
            ]);
    }
    
    protected static function generateReviewSummary($get): HtmlString
    {
        $title = $get('title') ?? 'Untitled';
        $venue = $get('venue_name') ?? 'No venue';
        $date = $get('event_date') 
            ? \Carbon\Carbon::parse($get('event_date'))->format('M d, Y g:i A') 
            : 'No date';
        $ticketTypes = $get('ticket_types') ?? [];
        $currency = $get('currency') ?? 'KES';
        
        $ticketInfo = '';
        foreach ($ticketTypes as $ticket) {
            $name = $ticket['name'] ?? 'Unnamed';
            $price = $ticket['price'] ?? 0;
            $quantity = $ticket['quantity'] ?? 0;
            $ticketInfo .= "<li>{$name} - {$currency} {$price} ({$quantity} available)</li>";
        }
        
        return new HtmlString("
            <div class='space-y-2'>
                <p><strong>Title:</strong> {$title}</p>
                <p><strong>Venue:</strong> {$venue}</p>
                <p><strong>Date:</strong> {$date}</p>
                <p><strong>Ticket Types:</strong></p>
                <ul class='list-disc pl-5'>{$ticketInfo}</ul>
            </div>
        ");
    }
}