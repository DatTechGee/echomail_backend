<!DOCTYPE html>
<html>
<head>
    <title>EchoMail - Your Verification Code</title>
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
            text-align: center;
        }
        .title {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 20px;
            font-weight: 700;
        }
        .message {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 30px;
            text-align: left;
            line-height: 1.7;
        }
        .otp-container {
            background: linear-gradient(135deg, #dbeafe, #e0e7ff);
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            border: 2px dashed #3b82f6;
            position: relative;
        }
        .otp-container::before {
            content: '🔐';
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 8px 12px;
            border-radius: 50%;
            font-size: 20px;
        }
        .otp-label {
            font-size: 14px;
            color: #1e40af;
            font-weight: 600;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .otp-code {
            font-size: 42px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #1f2937;
            font-family: 'Courier New', monospace;
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            display: inline-block;
            margin: 10px 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .expiry-notice {
            font-size: 14px;
            color: #1e40af;
            font-weight: 600;
            margin-top: 15px;
        }
        .security-note {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            border-left: 4px solid #ef4444;
            text-align: left;
        }
        .security-note h4 {
            color: #dc2626;
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: 700;
        }
        .security-note p {
            margin: 0;
            color: #991b1b;
            font-size: 14px;
            line-height: 1.6;
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
            .otp-code {
                font-size: 36px;
                letter-spacing: 4px;
                padding: 15px;
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
            <div class="title">Verification Required</div>

            <div class="message">
                <p>We've received a request that requires verification of your identity. Please use the verification code below to complete your action securely on your <span class="brand-highlight">EchoMail</span> dashboard.</p>
            </div>

            <div class="otp-container">
                <div class="otp-label">Your Verification Code</div>
                <div class="otp-code">{{ $otp }}</div>
                <div class="expiry-notice">⏰ This code expires in 10 minutes</div>
            </div>

            <div class="security-note">
                <h4>🛡️ Security Reminder</h4>
                <p><strong>Never share this code with anyone.</strong> The EchoMail team will never ask for your verification code via phone, email, or any other method. If you didn't request this code, please ignore this email or contact our support team immediately.</p>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} EchoMail. All rights reserved.</p>
            <p>Powerful email campaigns made simple.</p>
        </div>
    </div>
</body>
</html>
