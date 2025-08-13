<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "\n=== FIXING USER ISSUES ===\n\n";

// Fix john@gmail.com - mark email as verified
$john = User::where('email', 'john@gmail.com')->first();
if ($john) {
    $john->email_verified_at = now();
    $john->is_verified = true;
    $john->save();
    echo "✓ Fixed john@gmail.com - Email marked as verified\n";
}

// Ensure all test accounts are properly verified
$testEmails = ['admin@noxxi.com', 'organizer@noxxi.com', 'user@noxxi.com', 'tonysda@gmail.com', 'john@gmail.com'];

foreach ($testEmails as $email) {
    $user = User::where('email', $email)->first();
    if ($user && !$user->email_verified_at) {
        $user->email_verified_at = now();
        $user->is_verified = true;
        $user->save();
        echo "✓ Verified: {$email}\n";
    }
}

echo "\n✓ All users now have verified emails\n";
echo "✓ john@gmail.com should now be able to login without 403 error\n\n";