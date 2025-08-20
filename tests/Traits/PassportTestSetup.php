<?php

namespace Tests\Traits;

use Laravel\Passport\Client;
use Laravel\Passport\Passport;

trait PassportTestSetup
{
    /**
     * Setup Passport for testing
     */
    protected function setUpPassport(): void
    {
        // Run Passport keys
        $this->artisan('passport:keys', ['--force' => true]);

        // Create personal access client if not exists
        $personalAccessClient = Client::where('personal_access_client', true)->first();

        if (! $personalAccessClient) {
            // Create personal access client using artisan command
            $this->artisan('passport:client', [
                '--personal' => true,
                '--name' => 'Test Personal Access Client',
                '--no-interaction' => true,
            ]);
        }

        // Set token expiry for tests
        Passport::tokensExpireIn(now()->addMinutes(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addHours(24));
    }
}
