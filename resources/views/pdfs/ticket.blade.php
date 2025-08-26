<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - {{ $eventData['title'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .ticket-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header .event-date {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .ticket-body {
            padding: 30px;
        }
        
        .qr-section {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px dashed #ddd;
            margin-bottom: 30px;
        }
        
        .qr-code {
            display: inline-block;
            padding: 15px;
            background: white;
            border: 2px solid #333;
            border-radius: 10px;
        }
        
        .qr-code img {
            width: 200px;
            height: 200px;
        }
        
        .ticket-code {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin-top: 15px;
            letter-spacing: 2px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-block {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .venue-section {
            background: #f0f0f0;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .venue-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .venue-address {
            color: #666;
            line-height: 1.6;
        }
        
        .instructions {
            background: #fff9e6;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        
        .instructions h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .instructions ul {
            margin-left: 20px;
            color: #666;
            line-height: 1.8;
        }
        
        .footer {
            background: #333;
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .footer p {
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .footer .support {
            color: #aaa;
            font-size: 12px;
        }
        
        @media print {
            body {
                background: white;
            }
            
            .ticket-container {
                box-shadow: none;
            }
        }
        
        .ticket-type-badge {
            display: inline-block;
            background: #764ba2;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .validity-notice {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <!-- Header -->
        <div class="header">
            <h1>{{ $eventData['title'] }}</h1>
            <div class="event-date">{{ $eventData['date'] }} at {{ $eventData['time'] }}</div>
            <div class="ticket-type-badge">{{ $ticketData['type'] }}</div>
        </div>
        
        <!-- QR Code Section -->
        <div class="ticket-body">
            <div class="qr-section">
                <div class="qr-code">
                    @if($qrCodeUrl)
                        <img src="{{ $qrCodeUrl }}" alt="QR Code">
                    @else
                        <div style="width: 200px; height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                            <span>QR Code</span>
                        </div>
                    @endif
                </div>
                <div class="ticket-code">{{ $ticketData['code'] }}</div>
            </div>
            
            <!-- Ticket Information -->
            <div class="info-grid">
                <div class="info-block">
                    <div class="info-label">Ticket Holder</div>
                    <div class="info-value">{{ $ticketData['holder_name'] }}</div>
                </div>
                
                <div class="info-block">
                    <div class="info-label">Email</div>
                    <div class="info-value">{{ $ticketData['holder_email'] }}</div>
                </div>
                
                @if($ticketData['seat_section'])
                <div class="info-block">
                    <div class="info-label">Section</div>
                    <div class="info-value">{{ $ticketData['seat_section'] }}</div>
                </div>
                @endif
                
                @if($ticketData['seat_number'])
                <div class="info-block">
                    <div class="info-label">Seat</div>
                    <div class="info-value">{{ $ticketData['seat_number'] }}</div>
                </div>
                @endif
                
                <div class="info-block">
                    <div class="info-label">Booking Reference</div>
                    <div class="info-value">{{ $booking->booking_reference }}</div>
                </div>
                
                <div class="info-block">
                    <div class="info-label">Organizer</div>
                    <div class="info-value">{{ $eventData['organizer'] }}</div>
                </div>
            </div>
            
            <!-- Venue Information -->
            <div class="venue-section">
                <div class="venue-title">{{ $eventData['venue'] }}</div>
                <div class="venue-address">
                    {{ $eventData['address'] }}<br>
                    {{ $eventData['city'] }}
                </div>
            </div>
            
            <!-- Instructions -->
            <div class="instructions">
                <h3>Important Instructions</h3>
                <ul>
                    <li>Please arrive at least 30 minutes before the event starts</li>
                    <li>Present this QR code at the entrance for scanning</li>
                    <li>Keep this ticket safe - screenshot or print it</li>
                    <li>This ticket is valid for one entry only</li>
                </ul>
            </div>
            
            <!-- Validity Notice -->
            @if($validityData['valid_from'] || $validityData['valid_until'])
            <div class="validity-notice">
                <strong>Validity Period:</strong>
                @if($validityData['valid_from'])
                    From {{ $validityData['valid_from'] }}
                @endif
                @if($validityData['valid_until'])
                    Until {{ $validityData['valid_until'] }}
                @endif
            </div>
            @endif
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Powered by Noxxi - Your African Events Platform</p>
            <p class="support">Need help? Visit noxxi.com/support or contact the event organizer</p>
        </div>
    </div>
</body>
</html>