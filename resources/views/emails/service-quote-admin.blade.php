<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Quote Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #DC2626;
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px 20px;
            border: 1px solid #e5e7eb;
        }
        .alert-badge {
            background-color: #FEE2E2;
            color: #991B1B;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .service-details, .customer-details, .quote-details {
            background-color: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 6px;
            border-left: 4px solid #DC2626;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #DC2626;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-row {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #4b5563;
        }
        .value {
            color: #1f2937;
        }
        .service-title {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .button {
            display: inline-block;
            background-color: #DC2626;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-size: 14px;
        }
        .highlight {
            background-color: #FEF3C7;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #F59E0B;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">⚠️ New Quote Request</h1>
    </div>
    
    <div class="content">
        <div style="text-align: center;">
            <span class="alert-badge">🔔 ACTION REQUIRED</span>
        </div>
        
        <p>Hello Admin,</p>
        
        <p>A new quote request has been submitted and requires your immediate attention.</p>
        
        @if($service)
        <div class="service-details">
            <div class="section-title">📋 Service Details</div>
            <div class="service-title">{{ $service->title }}</div>
            @if($service->short_description)
            <p style="color: #6b7280; margin: 10px 0;">{{ $service->short_description }}</p>
            @endif
            <div class="info-row">
                <span class="label">Category:</span>
                <span class="value">{{ $service->category->name ?? 'N/A' }}</span>
            </div>
            @if($service->price)
            <div class="info-row">
                <span class="label">Base Price:</span>
                <span class="value" style="color: #DC2626; font-weight: bold;">${{ number_format($service->price, 2) }}</span>
            </div>
            @endif
        </div>
        @endif

        @if($quote->delivery)
        <div class="service-details">
            <div class="section-title">🚚 Delivery Option</div>
            <div class="service-title">{{ $quote->delivery->title }}</div>
            @if($quote->delivery->description)
            <p style="color: #6b7280; margin: 10px 0;">{{ $quote->delivery->description }}</p>
            @endif
            <div class="info-row">
                <span class="label">Delivery Cost:</span>
                <span class="value" style="color: #DC2626; font-weight: bold;">${{ number_format($quote->delivery->price, 2) }}</span>
            </div>
        </div>
        @endif
        
        <div class="customer-details">
            <div class="section-title">👤 Customer Information</div>
            <div class="info-row">
                <span class="label">Name:</span>
                <span class="value">{{ $user->name }}</span>
            </div>
            <div class="info-row">
                <span class="label">Email:</span>
                <span class="value">{{ $user->email }}</span>
            </div>
            @if($user->phone)
            <div class="info-row">
                <span class="label">Phone:</span>
                <span class="value">{{ $user->phone }}</span>
            </div>
            @endif
        </div>
        
        <div class="quote-details">
            <div class="section-title">📝 Quote Information</div>
            <div class="info-row">
                <span class="label">Quote ID:</span>
                <span class="value">#{{ $quote->id }}</span>
            </div>
            <div class="info-row">
                <span class="label">Type:</span>
                <span class="value">{{ ucfirst($quote->type) }}</span>
            </div>
            <div class="info-row">
                <span class="label">Submitted:</span>
                <span class="value">{{ $quote->created_at->format('F d, Y h:i A') }}</span>
            </div>
        </div>
        
        <div class="highlight">
            <strong>⏰ Response Time:</strong> Please review and respond to this quote request within 24-48 hours to ensure excellent customer service.
        </div>
        
        <div style="text-align: center;">
            <a href="{{ url('/quotes/' . $quote->id) }}" class="button">Review Quote Request</a>
        </div>
        
        <p style="margin-top: 20px; color: #6b7280; font-style: italic;">
            This quote request requires your professional assessment. Please log in to the admin panel to provide a detailed quote.
        </p>
    </div>
    
    <div class="footer">
        <p>This is an automated notification from DocAssist Admin System.</p>
        <p>&copy; {{ date('Y') }} DocAssist. All rights reserved.</p>
    </div>
</body>
</html>
