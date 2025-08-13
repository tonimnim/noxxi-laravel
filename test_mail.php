<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Mail;

try {
    Mail::raw('Test email verification code: 123456', function ($message) {
        $message->to('test@example.com')
                ->subject('Test Email Verification');
    });
    
    echo "Email sent successfully to log!\n";
    echo "Check storage/logs/laravel.log for the email content.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}