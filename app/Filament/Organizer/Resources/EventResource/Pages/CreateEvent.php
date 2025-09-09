<?php

namespace App\Filament\Organizer\Resources\EventResource\Pages;

use App\Filament\Organizer\Resources\EventResource;
use App\Filament\Organizer\Resources\EventResource\Forms;
use App\Services\CloudinaryService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

        // Default to published status (publish now)
        if (!isset($data['status'])) {
            $data['status'] = 'published';
        }

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

        // Handle media uploads - Always use Cloudinary
        if (! empty($data['media'])) {
            $processedMedia = [];
            $cloudinary = new CloudinaryService();
            
            foreach ($data['media'] as $mediaPath) {
                $filePath = storage_path('app/public/' . $mediaPath);
                
                if (!file_exists($filePath)) {
                    continue;
                }
                
                try {
                    // Create UploadedFile instance for Cloudinary
                    $uploadedFile = new \Illuminate\Http\UploadedFile(
                        $filePath,
                        basename($filePath),
                        mime_content_type($filePath),
                        null,
                        true
                    );
                    
                    // Upload to Cloudinary
                    $result = $cloudinary->uploadImage($uploadedFile, 'events');
                    
                    if ($result['success']) {
                        $processedMedia[] = [
                            'cloudinary_id' => $result['public_id'],
                            'url' => $result['url'],
                            'width' => $result['width'],
                            'height' => $result['height'],
                            'format' => $result['format'],
                            'size' => $result['size'],
                        ];
                    } else {
                        Log::error('Cloudinary upload failed for ' . $mediaPath . ': ' . ($result['error'] ?? 'Unknown error'));
                    }
                } catch (\Exception $e) {
                    Log::error('Exception during Cloudinary upload: ' . $e->getMessage());
                } finally {
                    // Always delete local file
                    @unlink($filePath);
                }
            }
            
            // Update media field with Cloudinary data
            $data['media'] = $processedMedia;
            
            // Set cover image URL from first image
            if (isset($processedMedia[0]['url'])) {
                $data['cover_image_url'] = $processedMedia[0]['url'];
            }
        }

        // Remove temporary fields
        unset($data['agree_terms']);
        unset($data['media_file_names']);

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
