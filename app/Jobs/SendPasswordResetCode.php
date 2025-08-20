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

class SendPasswordResetCode implements ShouldQueue
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
     * The reset code.
     */
    protected string $code;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $code)
    {
        $this->user = $user;
        $this->code = $code;

        // Use high priority queue for password reset codes
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
                    "You requested a password reset for your Noxxi account.\n\n".
                    "Your password reset code is: {$this->code}\n\n".
                    "This code will expire in 15 minutes.\n\n".
                    'If you did not request this reset, please ignore this email.',
                    function ($message) {
                        $message->to($this->user->email)
                            ->subject('Noxxi - Password Reset Code');
                    }
                );

                Log::info('Password reset email sent', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                ]);
            } else {
                // In development, log the code
                Log::info("Password reset code for {$this->user->email}: {$this->code}");
            }

            // If phone number is available and user prefers SMS, send SMS too
            if ($this->user->phone_number && $this->shouldSendSMS()) {
                $this->sendSMS();
            }
        } catch (\Exception $e) {
            Log::error('Failed to send password reset code', [
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
        // Check if phone number is from African countries where SMS is preferred
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
            '+237', // Cameroon
            '+212', // Morocco
            '+216', // Tunisia
            '+213', // Algeria
            '+251', // Ethiopia
        ];

        foreach ($africanPrefixes as $prefix) {
            if (str_starts_with($this->user->phone_number, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send SMS password reset code
     */
    protected function sendSMS(): void
    {
        // In production, integrate with SMS gateway (e.g., Africa's Talking, Twilio)
        if (app()->environment('production')) {
            // Example SMS integration for African markets
            // $smsService = app(SMSService::class);
            // $message = "Noxxi Password Reset Code: {$this->code}. Valid for 15 minutes.";
            // $smsService->send($this->user->phone_number, $message);

            Log::info('Password reset SMS would be sent in production', [
                'phone' => $this->user->phone_number,
            ]);
        } else {
            Log::info("SMS password reset code for {$this->user->phone_number}: {$this->code}");
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send password reset code after retries', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);

        // In production, notify support team
        if (app()->environment('production')) {
            // Notify support team about critical failure
            Log::critical('Password reset delivery failed for user', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['password-reset', 'user:'.$this->user->id];
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        // Give up retrying after 5 minutes
        return now()->addMinutes(5);
    }
}
