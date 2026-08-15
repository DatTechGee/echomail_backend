<!DOCTYPE html>
<html>
<head>
    <title>{{ $subscriber->status === 'pending' ? 'Confirm your subscription' : 'Welcome to EchoMail!' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #1f2937;
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
            background: linear-gradient(135deg, #2563EB 0%, #1E40AF 100%);
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
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.95) 0%, rgba(30, 64, 175, 0.95) 100%);
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
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        .tagline {
            font-size: 16px;
            opacity: 0.98;
            font-weight: 400;
        }
        .content {
            padding: 40px 30px;
            background-color: #ffffff;
        }
        .title {
            font-size: 28px;
            color: #111827;
            margin-bottom: 20px;
            font-weight: 700;
            text-align: center;
        }
        .message {
            font-size: 16px;
            color: #374151;
            margin-bottom: 30px;
            line-height: 1.7;
        }
        .welcome-box {
            background: linear-gradient(135deg, #dbeafe, #eff6ff);
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            border: 2px solid #2563EB;
            text-align: center;
        }
        .welcome-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .welcome-text {
            font-size: 18px;
            color: #1E40AF;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .cta-box {
            background: linear-gradient(135deg, #dbeafe, #eff6ff);
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            border: 2px solid #2563EB;
            text-align: center;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #2563EB, #1E40AF);
            color: #ffffff;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 50px;
            font-size: 17px;
            font-weight: 700;
            margin: 15px 0 5px;
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.35);
        }
        .cta-hint {
            font-size: 13px;
            color: #1E40AF;
            margin-top: 10px;
        }
        .benefits {
            background: #f8fafc;
            padding: 25px;
            border-radius: 10px;
            margin: 25px 0;
            border: 1px solid #e5e7eb;
        }
        .benefits h3 {
            color: #111827;
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
            margin-bottom: 10px;
            color: #374151;
            position: relative;
            padding-left: 28px;
            line-height: 1.6;
        }
        .benefits li::before {
            content: '\2713';
            position: absolute;
            left: 0;
            color: #2563EB;
            font-weight: bold;
            font-size: 18px;
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
            color: #7f1d1d;
            font-size: 14px;
        }
        .unsubscribe a {
            color: #991b1b;
            text-decoration: underline;
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
            background: linear-gradient(135deg, #2563EB, #1E40AF);
            height: 4px;
            width: 100%;
        }
        .brand-highlight {
            color: #2563EB;
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
                <div class="tagline">Send emails that get opened</div>
            </div>
        </div>

        <div class="divider"></div>

        <div class="content">
            @if ($subscriber->status === 'pending')
                <div class="title">Please Confirm Your Subscription</div>

                <div class="message">
                    <p>Hi{{ $subscriber->name ? ' ' . $subscriber->name : '' }},</p>
                    <p>Thanks for subscribing to <span class="brand-highlight">EchoMail</span>! To finish subscribing, please confirm your email address by clicking the button below.</p>
                </div>

                <div class="cta-box">
                    <div class="welcome-icon">&#9993;</div>
                    <div class="welcome-text">Confirm My Subscription</div>
                    <p style="color: #374151; margin: 0;">One quick click and you're all set.</p>
                    <a href="{{ $subscriber->verify_url }}" class="cta-button">Confirm Subscription</a>
                    <div class="cta-hint">If the button doesn't work, copy this link into your browser:<br>{{ $subscriber->verify_url }}</div>
                </div>

                <div class="message">
                    <p>If you didn't sign up for this list, you can safely ignore this email and your address will be removed automatically.</p>
                </div>

                <div class="unsubscribe">
                    <p>Manage your subscription preferences <a href="{{ $subscriber->preferences_url }}">here</a>.</p>
                </div>
            @else
                <div class="title">Welcome to EchoMail!</div>

                <div class="message">
                    <p>Hi{{ $subscriber->name ? ' ' . $subscriber->name : '' }},</p>
                    <p>Thank you for subscribing to <span class="brand-highlight">EchoMail</span>! We're excited to have you as part of our growing community.</p>
                </div>

                <div class="welcome-box">
                    <div class="welcome-icon">&#127881;</div>
                    <div class="welcome-text">You're Subscribed!</div>
                    <p style="color: #374151; margin: 0;">You'll receive our latest updates and campaigns</p>
                </div>

                <div class="benefits">
                    <h3>What Awaits You:</h3>
                    <ul>
                        <li>Create and send beautiful email campaigns</li>
                        <li>Track opens, clicks, and engagement in real time</li>
                        <li>Automate your newsletters and workflows</li>
                        <li>Reach every inbox with confidence</li>
                    </ul>
                </div>

                <div class="message">
                    <p>We're building something special and can't wait to share it with you. Stay tuned for updates!</p>
                    <p>Best regards,<br>The EchoMail Team</p>
                </div>

                <div class="unsubscribe">
                    <p>Don't want to receive these emails? <a href="{{ $subscriber->unsubscribe_url }}">Unsubscribe here</a> &middot; <a href="{{ $subscriber->preferences_url }}">Preferences</a></p>
                </div>
            @endif
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} EchoMail. All rights reserved.</p>
            <p>Send emails that get opened.</p>
        </div>
    </div>
</body>
</html>
