<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "\n=== USER STATUS CHECK ===\n\n";

$users = ['john@gmail.com', 'tonysda@gmail.com'];

foreach ($users as $email) {
    $user = User::where('email', $email)->with('organizer')->first();
    
    if ($user) {
        echo "User: {$user->full_name} ({$email})\n";
        echo "  Role: {$user->role}\n";
        echo "  Active: " . ($user->is_active ? 'YES' : 'NO') . "\n";
        echo "  Verified: " . ($user->is_verified ? 'YES' : 'NO') . "\n";
        echo "  Email Verified: " . ($user->email_verified_at ? 'YES' : 'NO') . "\n";
        
        if ($user->organizer) {
            echo "  Organizer Profile:\n";
            echo "    Business: {$user->organizer->business_name}\n";
            echo "    Active: " . ($user->organizer->is_active ? 'YES' : 'NO') . "\n";
            echo "    Approved: " . ($user->organizer->approved_at ? 'YES' : 'NO') . "\n";
        }
        echo "\n";
    }
}