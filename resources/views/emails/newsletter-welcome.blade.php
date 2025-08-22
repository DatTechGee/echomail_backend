<!DOCTYPE html>
<html>
<head>
    <title>Welcome to EchoMail Newsletter!</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #374151;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
            position: relative;
        }
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.9) 0%, rgba(139, 92, 246, 0.9) 100%);
        }
        .header-content {
            position: relative;
            z-index: 1;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .tagline {
            font-size: 16px;
            opacity: 0.95;
            font-weight: 300;
        }
        .content {
            padding: 40px 30px;
            background-color: #ffffff;
        }
        .title {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 20px;
            font-weight: 700;
            text-align: center;
        }
        .message {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 30px;
            line-height: 1.7;
        }
        .welcome-box {
            background: linear-gradient(135deg, #dbeafe, #e0e7ff);
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            border: 2px solid #3b82f6;
            text-align: center;
        }
        .welcome-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .welcome-text {
            font-size: 18px;
            color: #1e40af;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .benefits {
            background: #f8fafc;
            padding: 25px;
            border-radius: 10px;
            margin: 25px 0;
        }
        .benefits h3 {
            color: #1f2937;
            margin: 0 0 15px 0;
            font-size: 18px;
            font-weight: 700;
        }
        .benefits ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .benefits li {
            margin-bottom: 8px;
            color: #4b5563;
            position: relative;
            padding-left: 25px;
        }
        .benefits li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
        .unsubscribe {
            background: #fef2f2;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            border-left: 4px solid #ef4444;
            text-align: center;
        }
        .unsubscribe p {
            margin: 0;
            color: #991b1b;
            font-size: 14px;
        }
        .unsubscribe a {
            color: #dc2626;
            text-decoration: none;
            font-weight: 600;
        }
        .footer {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            padding: 25px 30px;
            text-align: center;
            color: #d1d5db;
            font-size: 14px;
        }
        .divider {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            height: 4px;
            width: 100%;
        }
        .brand-highlight {
            color: #3b82f6;
            font-weight: 600;
        }

        @media (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 8px;
            }
            .header, .content {
                padding: 30px 20px;
            }
            .logo {
                font-size: 28px;
            }
            .title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="logo">EchoMail</div>
                <div class="tagline">Email Campaign Management</div>
            </div>
        </div>

        <div class="divider"></div>

        <div class="content">
            <div class="title">Welcome to Our Newsletter!</div>

            <div class="message">
                <p>Hi{{ $subscriber->name ? ' ' . $subscriber->name : '' }},</p>
                <p>Thank you for subscribing to the <span class="brand-highlight">EchoMail</span> newsletter! We're excited to have you as part of our community of email marketing enthusiasts.</p>
            </div>

            <div class="welcome-box">
                <div class="welcome-icon">🎉</div>
                <div class="welcome-text">You're Now Subscribed!</div>
                <p style="color: #4b5563; margin: 0;">Get ready for valuable insights delivered to your inbox</p>
            </div>

            <div class="benefits">
                <h3>What to Expect:</h3>
                <ul>
                    <li>Weekly email marketing tips and strategies</li>
                    <li>Exclusive content and industry insights</li>
                    <li>Early access to new features and updates</li>
                    <li>Case studies from successful campaigns</li>
                    <li>Best practices from email marketing experts</li>
                </ul>
            </div>

            <div class="message">
                <p>We respect your privacy and will never spam you. Our newsletters are carefully crafted to provide real value to help you succeed with your email marketing efforts.</p>
                <p>Stay tuned for your first newsletter coming soon!</p>
                <p>Best regards,<br>The EchoMail Team</p>
            </div>

            <!-- <div class="unsubscribe">
                <p>Don't want to receive these emails? <a href="{{ $subscriber->unsubscribe_url }}">Unsubscribe here</a></p>
            </div> -->
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} EchoMail. All rights reserved.</p>
            <p>Powerful email campaigns made simple.</p>
        </div>
    </div>
</body>
</html>
