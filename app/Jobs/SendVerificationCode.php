<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendVerificationCode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 30;

    /**
     * The user instance.
     */
    protected User $user;

    /**
     * The verification code.
     */
    protected string $code;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $code)
    {
        $this->user = $user;
        $this->code = $code;

        // Use high priority queue for verification codes
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // In production, send actual email
            if (app()->environment('production')) {
                // Send email using Mail facade
                Mail::raw(
                    "Your verification code is: {$this->code}\n\nThis code will expire in 10 minutes.",
                    function ($message) {
                        $message->to($this->user->email)
                            ->subject('Noxxi - Email Verification Code');
                    }
                );

                Log::info('Verification email sent', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                ]);
            } else {
                // In development, log the code
                Log::info("Email verification code for {$this->user->email}: {$this->code}");
            }

            // If phone number is available, send SMS too (for African markets)
            if ($this->user->phone_number && $this->shouldSendSMS()) {
                $this->sendSMS();
            }
        } catch (\Exception $e) {
            Log::error('Failed to send verification code', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Determine if SMS should be sent based on phone number region
     */
    protected function shouldSendSMS(): bool
    {
        // Check if phone number is from African countries
        $africanPrefixes = [
            '+254', // Kenya
            '+234', // Nigeria
            '+27',  // South Africa
            '+233', // Ghana
            '+256', // Uganda
            '+255', // Tanzania
            '+20',  // Egypt
            '+263', // Zimbabwe
            '+265', // Malawi
            '+260', // Zambia
        ];

        foreach ($africanPrefixes as $prefix) {
            if (str_starts_with($this->user->phone_number, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send SMS verification code
     */
    protected function sendSMS(): void
    {
        // In production, integrate with SMS gateway (e.g., Africa's Talking, Twilio)
        if (app()->environment('production')) {
            // Example SMS integration
            // $smsService = app(SMSService::class);
            // $smsService->send($this->user->phone_number, "Your Noxxi verification code: {$this->code}");

            Log::info('SMS would be sent in production', [
                'phone' => $this->user->phone_number,
            ]);
        } else {
            Log::info("SMS verification code for {$this->user->phone_number}: {$this->code}");
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send verification code after retries', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['verification', 'user:'.$this->user->id];
    }
}
