<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotes Reply</title>
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
        <h1 style="margin: 0;">Quotes Reply</h1>
    </div>
    
    <div class="content">
        <p>Hello <strong>{{ $quote['name'] }}</strong>,</p>
        
        <p>Thank you for submitting your custom document quote request to <strong>DocAssist</strong>! We have received your inquiry and our team will review the details shortly.</p>
        
        <div class="details-box">
           {{ $quote['reply'] }}
        </div>
        
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
