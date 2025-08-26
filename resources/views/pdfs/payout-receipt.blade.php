<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payout Receipt - {{ $payout->reference }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #f59e0b;
            margin-bottom: 10px;
        }
        
        .company-info {
            color: #666;
            font-size: 12px;
        }
        
        .receipt-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin: 30px 0;
            color: #333;
        }
        
        .receipt-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .meta-section {
            flex: 1;
        }
        
        .meta-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .meta-value {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .info-item {
            padding: 10px;
            background: #f9fafb;
            border-radius: 5px;
        }
        
        .info-label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        
        .info-value {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .breakdown-table th {
            background: #f3f4f6;
            padding: 10px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
        }
        
        .breakdown-table td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        
        .breakdown-table tr:last-child td {
            border-bottom: none;
        }
        
        .amount-right {
            text-align: right;
        }
        
        .total-row {
            font-weight: bold;
            background: #f9fafb;
        }
        
        .total-row.final {
            background: #fef3c7;
            font-size: 16px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            background: #10b981;
            color: white;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            color: rgba(0,0,0,0.05);
            font-weight: bold;
            z-index: -1;
        }
        
        @media print {
            .container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="watermark">PAID</div>
    
    <div class="container">
        <div class="header">
            <div class="logo">NOXXI</div>
            <div class="company-info">
                {{ $company['address'] }} | {{ $company['phone'] }}<br>
                {{ $company['email'] }} | {{ $company['website'] }}
            </div>
        </div>

        <h1 class="receipt-title">PAYOUT RECEIPT</h1>

        <div class="receipt-meta">
            <div class="meta-section">
                <div class="meta-label">Receipt Number</div>
                <div class="meta-value">{{ $payout->reference }}</div>
            </div>
            <div class="meta-section" style="text-align: center;">
                <div class="meta-label">Status</div>
                <div class="meta-value">
                    <span class="status-badge">COMPLETED</span>
                </div>
            </div>
            <div class="meta-section" style="text-align: right;">
                <div class="meta-label">Issue Date</div>
                <div class="meta-value">{{ $issue_date->format('d M Y') }}</div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Organizer Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Business Name</div>
                    <div class="info-value">{{ $organizer->business_name }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Type</div>
                    <div class="info-value">{{ ucfirst($organizer->business_type) }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value">{{ $organizer->user->email ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value">{{ $organizer->user->phone_number ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Payout Details</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Period</div>
                    <div class="info-value">
                        {{ \Carbon\Carbon::parse($payout->period_start)->format('d M Y') }} - 
                        {{ \Carbon\Carbon::parse($payout->period_end)->format('d M Y') }}
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value">{{ $payment_method }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Details</div>
                    <div class="info-value">{{ $payment_details }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Completed Date</div>
                    <div class="info-value">{{ $payout->completed_at->format('d M Y, H:i') }}</div>
                </div>
            </div>
        </div>

        @if(count($transaction_summary) > 0)
        <div class="section">
            <h2 class="section-title">Transaction Summary</h2>
            <table class="breakdown-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th class="amount-right">Tickets</th>
                        <th class="amount-right">Revenue</th>
                        <th class="amount-right">Commission</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaction_summary as $summary)
                    <tr>
                        <td>{{ $summary['event_title'] }}</td>
                        <td class="amount-right">{{ $summary['transaction_count'] }}</td>
                        <td class="amount-right">{{ $currency }} {{ number_format($summary['total_amount'], 2) }}</td>
                        <td class="amount-right">{{ $currency }} {{ number_format($summary['commission'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="section">
            <h2 class="section-title">Financial Breakdown</h2>
            <table class="breakdown-table">
                <tbody>
                    <tr>
                        <td>Gross Revenue</td>
                        <td class="amount-right">{{ $currency }} {{ number_format($gross_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Platform Commission</td>
                        <td class="amount-right" style="color: #dc2626;">-{{ $currency }} {{ number_format($commission_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Processing Fee</td>
                        <td class="amount-right" style="color: #dc2626;">-{{ $currency }} {{ number_format($payout_fee, 2) }}</td>
                    </tr>
                    <tr class="total-row final">
                        <td>Net Amount Paid</td>
                        <td class="amount-right" style="color: #059669;">{{ $currency }} {{ number_format($net_amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <p>This is an official payout receipt from Noxxi Platform.</p>
            <p>For any queries, please contact us at {{ $company['email'] }}</p>
            <p style="margin-top: 10px;">
                Receipt generated on {{ now()->format('d M Y, H:i:s') }}
            </p>
        </div>
    </div>
</body>
</html>