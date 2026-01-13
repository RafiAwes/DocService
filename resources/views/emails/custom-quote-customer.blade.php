<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Quote Request Received</title>
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
        .details-box {
            background-color: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
            border-left: 4px solid #4F46E5;
        }
        .info-row {
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
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
        <h1 style="margin: 0;">📋 Custom Quote Request Received</h1>
    </div>
    
    <div class="content">
        <p>Hello <strong>{{ $customQuote->name }}</strong>,</p>
        
        <p>Thank you for submitting your custom document quote request to <strong>DocAssist</strong>! We have received your inquiry and our team will review the details shortly.</p>
        
        <div class="details-box">
            <div class="info-row">
                <span class="label">Quote ID:</span>
                <span class="value">#{{ $quote->id }}</span>
            </div>
            <div class="info-row">
                <span class="label">Submitted Date:</span>
                <span class="value">{{ $quote->created_at->format('F d, Y h:i A') }}</span>
            </div>
            <div class="info-row">
                <span class="label">Status:</span>
                <span class="value" style="color: #4F46E5; font-weight: bold;">Pending Review</span>
            </div>
        </div>
        
        <div class="details-box">
            <h3 style="margin-top: 0; color: #1f2937;">Your Document Requirements:</h3>
            <div class="info-row">
                <span class="label">Documents Needed:</span>
                <span class="value">{{ $customQuote->document_request }}</span>
            </div>
            <div class="info-row">
                <span class="label">Document Return Country:</span>
                <span class="value">{{ $customQuote->drc }}</span>
            </div>
            <div class="info-row">
                <span class="label">Document Use Country:</span>
                <span class="value">{{ $customQuote->duc }}</span>
            </div>
            <div class="info-row">
                <span class="label">Country of Residence:</span>
                <span class="value">{{ $customQuote->residence_country }}</span>
            </div>
            <div class="info-row">
                <span class="label">Contact Number:</span>
                <span class="value">{{ $customQuote->contact_number }}</span>
            </div>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ url('/quotes/' . $quote->id) }}" class="button">View Your Quote</a>
        </div>
        
        <p>Our specialized document services team will analyze your specific requirements and provide you with a detailed quote and timeline within <strong>24-48 hours</strong>.</p>
        
        <p>If you need to provide additional information or have any questions before we send the quote, please reply to this email or contact us directly.</p>
        
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
