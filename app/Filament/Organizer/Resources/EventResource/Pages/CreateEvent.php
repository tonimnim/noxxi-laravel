<?php

namespace App\Filament\Organizer\Resources\EventResource\Pages;

use App\Filament\Organizer\Resources\EventResource;
use App\Filament\Organizer\Resources\EventResource\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateEvent extends CreateRecord
{
    use HasWizard;

    protected static string $resource = EventResource::class;

    public function mount(): void
    {
        $organizer = Auth::user()->organizer;

        if (! $organizer) {
            Notification::make()
                ->danger()
                ->title('Organizer Profile Not Found')
                ->body('Your organizer profile could not be found. Please contact support.')
                ->persistent()
                ->send();

            $this->redirect(EventResource::getUrl('index'));

            return;
        }

        // Check if organizer account is verified by admin
        if (! $organizer->is_verified) {
            Notification::make()
                ->warning()
                ->title('Account Pending Verification')
                ->body('Your organizer account is pending admin verification. You will be able to create listings once your account is approved. This usually takes 24-48 hours.')
                ->persistent()
                ->send();

            $this->redirect(EventResource::getUrl('index'));

            return;
        }

        parent::mount();
    }

    protected function getSteps(): array
    {
        return [
            Forms\BasicInformationStep::make(),
            Forms\DateLocationStep::make(),
            Forms\TicketTypesStep::make(),
            Forms\MediaMarketingStep::make(),
            Forms\PoliciesTermsStep::make(),
            Forms\ReviewPublishStep::make(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['organizer_id'] = Auth::user()->organizer->id;

        // Generate QR secret key
        $data['qr_secret_key'] = Str::random(32);

        // Set first published date if publishing
        if ($data['status'] === 'published') {
            $data['published_at'] = now();
            $data['first_published_at'] = now();
        }

        // Calculate min and max prices from ticket types
        if (! empty($data['ticket_types'])) {
            $prices = array_column($data['ticket_types'], 'price');
            $data['min_price'] = min($prices);
            $data['max_price'] = max($prices);
        }

        // Set cover image from first uploaded image
        if (! empty($data['media']['images'])) {
            $data['cover_image_url'] = $data['media']['images'][0];
        }

        // Remove agree_terms field
        unset($data['agree_terms']);

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Listing Created Successfully';
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
