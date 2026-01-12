<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contact Notification</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f4f7; padding: 40px 0; margin: 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden; }
        
        /* Dynamic Header Color */
        .header { padding: 30px; text-align: center; color: #ffffff; }
        .header.admin { background-color: #2d3748; } /* Dark for Admin */
        .header.user { background-color: #3182ce; }  /* Blue for User */
        
        .header h1 { margin: 0; font-size: 24px; font-weight: normal; }
        
        .content { padding: 30px; color: #555; line-height: 1.6; }
        .intro { font-size: 16px; margin-bottom: 20px; }
        
        .data-box { background: #f9f9f9; padding: 20px; border-radius: 5px; border: 1px solid #eee; margin: 20px 0; }
        .label { font-weight: bold; color: #333; display: block; margin-bottom: 5px; }
        .value { margin-bottom: 15px; display: block; }
        
        .footer { background-color: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="header {{ $type === 'admin' ? 'admin' : 'user' }}">
            @if($type === 'admin')
                <h1>New Inquiry Received</h1>
            @else
                <h1>Message Sent Successfully!</h1>
            @endif
        </div>
        
        <div class="content">
            
            <div class="intro">
                @if($type === 'admin')
                    <p>Hello Admin,</p>
                    <p><strong>{{ $data['name'] }}</strong> has sent a new message via the contact form.</p>
                @else
                    <p>Hello {{ $data['name'] }},</p>
                    <p>Thank you for contacting us. <strong>Your message has been sent successfully.</strong></p>
                    <p>We have received your details and our team will be in touch with you very soon.</p>
                    <p>Here is a copy of your message for your records:</p>
                @endif
            </div>

            <div class="data-box">
                <span class="label">Subject / Email:</span>
                <span class="value">{{ $data['email'] }}</span>

                <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">

                <span class="label">Message:</span>
                <span class="value" style="white-space: pre-wrap;">{{ $data['message'] }}</span>
            </div>
            
            @if($type === 'user')
                <p>If you have any urgent queries, please reply to this email.</p>
            @endif
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>