<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use App\Models\Ticket;
use App\Services\QrCodeService;

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $ticket = Ticket::first();
    
    if (!$ticket) {
        echo "No tickets found\n";
        exit;
    }
    
    echo "Testing QR generation for ticket: {$ticket->id}\n";
    echo "Current QR path: {$ticket->qr_code}\n";
    
    // Load event relationship
    $ticket->load('event');
    
    if (!$ticket->event) {
        echo "No event found for ticket\n";
        exit;
    }
    
    echo "Event: {$ticket->event->title}\n";
    
    // Generate QR code
    $qrService = new QrCodeService();
    $result = $qrService->generateTicketQrCode($ticket);
    
    echo "QR generation result:\n";
    print_r($result);
    
    // Check if file was created
    $filePath = storage_path('app/public/tickets/qr/' . $ticket->id . '.png');
    if (file_exists($filePath)) {
        echo "QR file created successfully at: $filePath\n";
        echo "File size: " . filesize($filePath) . " bytes\n";
    } else {
        echo "QR file was NOT created\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}