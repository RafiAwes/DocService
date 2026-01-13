<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Custom Quote Request</title>
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
        .alert-badge {
            display: inline-block;
            background-color: #FCA5A5;
            color: #7F1D1D;
            padding: 8px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .content {
            background-color: #fef2f2;
            padding: 30px 20px;
            border: 2px solid #DC2626;
        }
        .details-box {
            background-color: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
            border-left: 4px solid #DC2626;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="alert-badge">⚠️ ACTION REQUIRED</div>
        <h1 style="margin: 10px 0 0 0;">New Custom Quote Request</h1>
    </div>
    
    <div class="content">
        <p style="color: #7F1D1D; font-weight: bold;">A new custom document quote request has been submitted and requires review.</p>
        
        <div class="details-box">
            <h3 style="margin-top: 0; color: #DC2626;">Customer Information</h3>
            <div class="info-row">
                <span class="label">Name:</span>
                <span class="value">{{ $customQuote->name }}</span>
            </div>
            <div class="info-row">
                <span class="label">Email:</span>
                <span class="value">{{ $user->email }}</span>
            </div>
            <div class="info-row">
                <span class="label">Contact Number:</span>
                <span class="value">{{ $customQuote->contact_number }}</span>
            </div>
            <div class="info-row">
                <span class="label">Country of Residence:</span>
                <span class="value">{{ $customQuote->residence_country }}</span>
            </div>
        </div>
        
        <div class="details-box">
            <h3 style="margin-top: 0; color: #DC2626;">Document Requirements</h3>
            <div class="info-row">
                <span class="label">Documents Requested:</span>
                <span class="value">{{ $customQuote->document_request }}</span>
            </div>
            <div class="info-row">
                <span class="label">Document Return Country (DRC):</span>
                <span class="value">{{ $customQuote->drc }}</span>
            </div>
            <div class="info-row">
                <span class="label">Document Use Country (DUC):</span>
                <span class="value">{{ $customQuote->duc }}</span>
            </div>
        </div>
        
        <div class="details-box">
            <h3 style="margin-top: 0; color: #DC2626;">Quote Details</h3>
            <div class="info-row">
                <span class="label">Quote ID:</span>
                <span class="value">#{{ $quote->id }}</span>
            </div>
            <div class="info-row">
                <span class="label">Type:</span>
                <span class="value">Custom Document Request</span>
            </div>
            <div class="info-row">
                <span class="label">Submitted:</span>
                <span class="value">{{ $quote->created_at->format('F d, Y h:i A') }}</span>
            </div>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ config('app.admin_url') }}/quotes/{{ $quote->id }}" class="button">Review Quote Request</a>
        </div>
        
        <p style="padding: 15px; background-color: #FEE2E2; border-radius: 6px; color: #991B1B;">
            <strong>Response Time Reminder:</strong> Please aim to respond to this quote request within 24-48 hours to maintain service quality standards.
        </p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} DocAssist Admin Alert System.</p>
    </div>
</body>
</html>
