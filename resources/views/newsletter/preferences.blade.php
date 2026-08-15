<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Preferences</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            max-width: 520px;
            width: 100%;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(37, 99, 235, 0.15);
            overflow: hidden;
        }
        .banner {
            background: linear-gradient(135deg, #2563EB, #1E40AF);
            padding: 30px 20px;
            color: white;
            text-align: center;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            letter-spacing: -0.5px;
        }
        .subtitle {
            font-size: 14px;
            opacity: 0.95;
            margin-top: 6px;
        }
        .body {
            padding: 30px;
        }
        .status {
            background: #eff6ff;
            border: 1px solid #93c5fd;
            color: #1e40af;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .intro {
            font-size: 14px;
            color: #374151;
            line-height: 1.7;
            margin-bottom: 20px;
        }
        .intro strong {
            color: #111827;
        }
        .option {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 16px 0;
            border-bottom: 1px solid #f0f0f0;
            gap: 16px;
        }
        .option:last-child {
            border-bottom: none;
        }
        .option-text strong {
            display: block;
            font-size: 15px;
            color: #111827;
            margin-bottom: 3px;
        }
        .option-text span {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.5;
        }
        input[type="checkbox"] {
            width: 22px;
            height: 22px;
            accent-color: #2563EB;
            cursor: pointer;
            margin-top: 2px;
        }
        .save {
            width: 100%;
            background: linear-gradient(135deg, #2563EB, #1E40AF);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
        }
        .save:hover {
            opacity: 0.92;
        }
        .footer-links {
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
        }
        .footer-links a {
            color: #2563EB;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="banner">
            <div class="logo">EchoMail</div>
            <div class="subtitle">Email Preferences</div>
        </div>
        <div class="body">
            @if (session('status'))
                <div class="status">{{ session('status') }}</div>
            @endif

            <div class="intro">
                Manage what you receive from <strong>EchoMail</strong> for <strong>{{ $subscriber->email }}</strong>.
            </div>

            <form method="POST" action="{{ route('newsletter.preferences.update', $subscriber->unsubscribe_token) }}">
                @csrf
                <div class="option">
                    <div class="option-text">
                        <strong>Campaign updates</strong>
                        <span>News about new features, tools, and what's happening.</span>
                    </div>
                    <input type="checkbox" name="email_updates" value="1"
                        {{ ($subscriber->preferences['email_updates'] ?? true) ? 'checked' : '' }}>
                </div>

                <div class="option">
                    <div class="option-text">
                        <strong>Product updates</strong>
                        <span>Announcements about launches, tools, and resources.</span>
                    </div>
                    <input type="checkbox" name="product_updates" value="1"
                        {{ ($subscriber->preferences['product_updates'] ?? true) ? 'checked' : '' }}>
                </div>

                <div class="option">
                    <div class="option-text">
                        <strong>Promotions</strong>
                        <span>Offers, discounts, and special opportunities.</span>
                    </div>
                    <input type="checkbox" name="promotions" value="1"
                        {{ ($subscriber->preferences['promotions'] ?? true) ? 'checked' : '' }}>
                </div>

                <button type="submit" class="save">Save Preferences</button>
            </form>

            <div class="footer-links">
                <a href="{{ $subscriber->unsubscribe_url }}">Unsubscribe from all emails</a>
            </div>
        </div>
    </div>
</body>
</html>
