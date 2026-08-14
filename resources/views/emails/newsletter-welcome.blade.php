<!DOCTYPE html>
<html>
<head>
    <title>{{ $subscriber->status === 'pending' ? 'Confirm your subscription' : 'Welcome to Levelup Xperience Community!' }}</title>
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
            background: linear-gradient(135deg, #2ECC71 0%, #1A9B7F 100%);
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
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.95) 0%, rgba(26, 155, 127, 0.95) 100%);
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
            background: linear-gradient(135deg, #d1f5e3, #e0f4ec);
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            border: 2px solid #2ECC71;
            text-align: center;
        }
        .welcome-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .welcome-text {
            font-size: 18px;
            color: #117550;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .cta-box {
            background: linear-gradient(135deg, #d1f5e3, #e0f4ec);
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            border: 2px solid #2ECC71;
            text-align: center;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #2ECC71, #1A9B7F);
            color: #ffffff;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 50px;
            font-size: 17px;
            font-weight: 700;
            margin: 15px 0 5px;
            box-shadow: 0 6px 15px rgba(26, 155, 127, 0.35);
        }
        .cta-hint {
            font-size: 13px;
            color: #117550;
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
            content: '✓';
            position: absolute;
            left: 0;
            color: #2ECC71;
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
            background: linear-gradient(135deg, #2ECC71, #1A9B7F);
            height: 4px;
            width: 100%;
        }
        .brand-highlight {
            color: #1A9B7F;
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
                <div class="logo">Levelup Xperience</div>
                <div class="tagline">Connect • Collaborate • Grow</div>
            </div>
        </div>

        <div class="divider"></div>

        <div class="content">
            @if ($subscriber->status === 'pending')
                <div class="title">Please Confirm Your Subscription</div>

                <div class="message">
                    <p>Hi{{ $subscriber->name ? ' ' . $subscriber->name : '' }},</p>
                    <p>Thanks for joining the waitlist for <span class="brand-highlight">Levelup Xperience</span>! To finish subscribing, please confirm your email address by clicking the button below.</p>
                </div>

                <div class="cta-box">
                    <div class="welcome-icon">✉️</div>
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
                <div class="title">Welcome to Our Community!</div>

                <div class="message">
                    <p>Hi{{ $subscriber->name ? ' ' . $subscriber->name : '' }},</p>
                    <p>Thank you for joining the waitlist for <span class="brand-highlight">Levelup Xperience</span>! We're excited to have you as part of our growing community of freelancers, content creators, and lifelong learners.</p>
                </div>

                <div class="welcome-box">
                    <div class="welcome-icon">🎉</div>
                    <div class="welcome-text">You're on the Waitlist!</div>
                    <p style="color: #374151; margin: 0;">Be the first to know when we launch</p>
                </div>

                <div class="benefits">
                    <h3>What Awaits You:</h3>
                    <ul>
                        <li>Connect with talented freelancers and creators worldwide</li>
                        <li>Access exclusive learning resources and workshops</li>
                        <li>Collaborate on exciting projects and opportunities</li>
                        <li>Early access to community features and events</li>
                        <li>Network with industry experts and mentors</li>
                    </ul>
                </div>

                <div class="message">
                    <p>We're building something special and can't wait to share it with you. As a waitlist member, you'll get priority access when we launch and exclusive benefits reserved just for early supporters.</p>
                    <p>Stay tuned for updates on our progress!</p>
                    <p>Best regards,<br>The Levelup Xperience Team</p>
                </div>

                <div class="unsubscribe">
                    <p>Don't want to receive these emails? <a href="{{ $subscriber->unsubscribe_url }}">Unsubscribe here</a> · <a href="{{ $subscriber->preferences_url }}">Preferences</a></p>
                </div>
            @endif
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Levelup Xperience. All rights reserved.</p>
            <p>Empowering communities to grow together.</p>
        </div>
    </div>
</body>
</html>
