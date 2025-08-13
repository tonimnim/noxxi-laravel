<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

try {
    // Create a test user
    $user = User::create([
        'id' => Str::uuid(),
        'full_name' => 'Test User',
        'email' => 'testuser@example.com',
        'phone_number' => '+1234567890',
        'password' => bcrypt('password123'),
        'role' => 'user',
    ]);

    echo "Created test user: {$user->email}\n";
    
    // Generate verification code (like in AuthController)
    $code = rand(100000, 999999);
    
    // Store in cache for 10 minutes
    cache()->put('verify_code_' . $user->id, (string)$code, 600);
    
    // Send verification email
    Mail::raw("Your verification code is: $code", function ($message) use ($user) {
        $message->to($user->email)->subject('Email Verification Code');
    });
    
    echo "Verification email sent! Code: $code\n";
    echo "Check storage/logs/laravel.log for the email content.\n";
    echo "User ID: {$user->id}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}