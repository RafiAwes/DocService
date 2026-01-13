<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Request Confirmation</title>
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
            background-color: #4F46E5;
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
        .service-details {
            background-color: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
            border-left: 4px solid #4F46E5;
        }
        .service-title {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .service-description {
            color: #6b7280;
            margin-bottom: 10px;
        }
        .price {
            font-size: 18px;
            color: #4F46E5;
            font-weight: bold;
        }
        .quote-info {
            background-color: white;
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .quote-info-item {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .quote-info-item:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: bold;
            color: #4b5563;
        }
        .button {
            display: inline-block;
            background-color: #4F46E5;
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
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">Quote Request Confirmed</h1>
    </div>
    
    <div class="content">
        <p>Hello <strong>{{ $user->name }}</strong>,</p>
        
        <p>Thank you for requesting a quote from <strong>DocAssist</strong>! We have successfully received your request and our team will review it shortly.</p>
        
        @if($service)
        <div class="service-details">
            <div class="label">Service Requested:</div>
            <div class="service-title">{{ $service->title }}</div>
            @if($service->short_description)
            <div class="service-description">{{ $service->short_description }}</div>
            @endif
            @if($service->price)
            <div class="price">Base Price: ${{ number_format($service->price, 2) }}</div>
            @endif
        </div>
        @endif

        @if($quote->delivery)
        <div class="service-details">
            <div class="label">Delivery Option:</div>
            <div class="service-title">{{ $quote->delivery->title }}</div>
            @if($quote->delivery->description)
            <div class="service-description">{{ $quote->delivery->description }}</div>
            @endif
            @if($quote->delivery->price)
            <div class="price">Delivery Cost: ${{ number_format($quote->delivery->price, 2) }}</div>
            @endif
        </div>
        @endif
        
        <div class="quote-info">
            <div class="quote-info-item">
                <span class="label">Quote ID:</span> #{{ $quote->id }}
            </div>
            <div class="quote-info-item">
                <span class="label">Request Date:</span> {{ $quote->created_at->format('F d, Y h:i A') }}
            </div>
            <div class="quote-info-item">
                <span class="label">Status:</span> Pending Review
            </div>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ url('/quotes/' . $quote->id) }}" class="button">View Quote Details</a>
        </div>
        
        <p>Our team will review your requirements and get back to you with a detailed quote within <strong>24-48 hours</strong>.</p>
        
        <p>If you have any questions, please don't hesitate to contact us.</p>
        
        <p style="margin-top: 30px;">
            Best regards,<br>
            <strong>DocAssist Team</strong>
        </p>
    </div>
    
    <div class="footer">
        <p>This is an automated message. Please do not reply directly to this email.</p>
        <p>&copy; {{ date('Y') }} DocAssist. All rights reserved.</p>
    </div>
</body>
</html>
