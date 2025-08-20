<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    /**
     * Send the given notification via SMS.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        // Get the SMS representation of the notification
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $data = $notification->toSms($notifiable);
        
        if (!isset($data['recipient']) || !isset($data['message'])) {
            return;
        }

        // Determine which SMS provider to use based on country
        $countryCode = $this->getCountryCode($data['recipient']);
        
        switch ($countryCode) {
            case '254': // Kenya
                $this->sendViaAfricasTalking($data);
                break;
            case '234': // Nigeria
                $this->sendViaTermii($data);
                break;
            case '27':  // South Africa
                $this->sendViaClickatell($data);
                break;
            case '233': // Ghana
                $this->sendViaHubtel($data);
                break;
            default:
                $this->sendViaTwilio($data); // Fallback to Twilio
        }
    }

    /**
     * Send SMS via Africa's Talking (Kenya, Uganda, Tanzania)
     */
    protected function sendViaAfricasTalking(array $data): void
    {
        if (!config('services.africastalking.api_key')) {
            Log::warning('Africa\'s Talking API key not configured');
            return;
        }

        try {
            $response = Http::withHeaders([
                'apiKey' => config('services.africastalking.api_key'),
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ])->asForm()->post('https://api.africastalking.com/version1/messaging', [
                'username' => config('services.africastalking.username'),
                'to' => $data['recipient'],
                'message' => $data['message'],
                'from' => config('services.africastalking.sender_id', 'NOXXI'),
            ]);

            if ($response->successful()) {
                Log::info('SMS sent via Africa\'s Talking', [
                    'recipient' => $data['recipient'],
                    'response' => $response->json(),
                ]);
            } else {
                Log::error('Failed to send SMS via Africa\'s Talking', [
                    'recipient' => $data['recipient'],
                    'error' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'provider' => 'Africa\'s Talking',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send SMS via Termii (Nigeria)
     */
    protected function sendViaTermii(array $data): void
    {
        if (!config('services.termii.api_key')) {
            Log::warning('Termii API key not configured');
            return;
        }

        try {
            $response = Http::post('https://api.ng.termii.com/api/sms/send', [
                'api_key' => config('services.termii.api_key'),
                'to' => $data['recipient'],
                'from' => config('services.termii.sender_id', 'NOXXI'),
                'sms' => $data['message'],
                'type' => 'plain',
                'channel' => 'generic',
            ]);

            if ($response->successful()) {
                Log::info('SMS sent via Termii', [
                    'recipient' => $data['recipient'],
                    'response' => $response->json(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'provider' => 'Termii',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send SMS via Clickatell (South Africa)
     */
    protected function sendViaClickatell(array $data): void
    {
        if (!config('services.clickatell.api_key')) {
            Log::warning('Clickatell API key not configured');
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => config('services.clickatell.api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://platform.clickatell.com/messages', [
                'content' => $data['message'],
                'to' => [$data['recipient']],
                'from' => config('services.clickatell.sender_id', 'NOXXI'),
            ]);

            if ($response->successful()) {
                Log::info('SMS sent via Clickatell', [
                    'recipient' => $data['recipient'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'provider' => 'Clickatell',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send SMS via Hubtel (Ghana)
     */
    protected function sendViaHubtel(array $data): void
    {
        if (!config('services.hubtel.client_id')) {
            Log::warning('Hubtel credentials not configured');
            return;
        }

        try {
            $response = Http::withBasicAuth(
                config('services.hubtel.client_id'),
                config('services.hubtel.client_secret')
            )->post('https://api.hubtel.com/v1/messages', [
                'from' => config('services.hubtel.sender_id', 'NOXXI'),
                'to' => $data['recipient'],
                'content' => $data['message'],
            ]);

            if ($response->successful()) {
                Log::info('SMS sent via Hubtel', [
                    'recipient' => $data['recipient'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'provider' => 'Hubtel',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send SMS via Twilio (Fallback/International)
     */
    protected function sendViaTwilio(array $data): void
    {
        if (!config('services.twilio.sid')) {
            Log::warning('Twilio credentials not configured');
            return;
        }

        try {
            $response = Http::withBasicAuth(
                config('services.twilio.sid'),
                config('services.twilio.token')
            )->asForm()->post(
                'https://api.twilio.com/2010-04-01/Accounts/' . config('services.twilio.sid') . '/Messages.json',
                [
                    'From' => config('services.twilio.from'),
                    'To' => $data['recipient'],
                    'Body' => $data['message'],
                ]
            );

            if ($response->successful()) {
                Log::info('SMS sent via Twilio', [
                    'recipient' => $data['recipient'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'provider' => 'Twilio',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Extract country code from phone number
     */
    protected function getCountryCode(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Common African country codes
        $countryCodes = ['254', '234', '27', '233', '256', '255', '20'];
        
        foreach ($countryCodes as $code) {
            if (str_starts_with($phone, $code)) {
                return $code;
            }
        }
        
        // Default to first 3 digits
        return substr($phone, 0, 3);
    }
}