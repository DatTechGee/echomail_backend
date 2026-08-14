<!DOCTYPE html>
<html>
<head>
    <title>Your Weekly EchoMail Summary</title>
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
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            padding: 36px 30px;
            text-align: center;
            color: white;
        }
        .logo {
            font-size: 26px;
            font-weight: bold;
            letter-spacing: -0.5px;
        }
        .subtitle {
            font-size: 14px;
            opacity: 0.95;
            margin-top: 6px;
        }
        .content {
            padding: 32px 30px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin: 24px 0;
        }
        .stat {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 18px;
            text-align: center;
        }
        .stat-value {
            font-size: 26px;
            font-weight: 700;
            color: #2563eb;
        }
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        .footer {
            background: #111827;
            padding: 22px 30px;
            text-align: center;
            color: #d1d5db;
            font-size: 13px;
        }
        .period {
            color: #374151;
            font-size: 14px;
            text-align: center;
            margin-bottom: 8px;
        }
        @media (max-width: 600px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">EchoMail</div>
            <div class="subtitle">Weekly Summary</div>
        </div>

        <div class="content">
            <div class="greeting">
                Hi {{ $user->first_name }},<br>
                Here's what happened in your mailing platform over the last week.
            </div>

            <div class="period">{{ $start->format('M d') }} – {{ $end->format('M d, Y') }}</div>

            <div class="stats-grid">
                <div class="stat">
                    <div class="stat-value">{{ $stats['campaigns_created'] }}</div>
                    <div class="stat-label">Campaigns Created</div>
                </div>
                <div class="stat">
                    <div class="stat-value">{{ number_format($stats['emails_sent']) }}</div>
                    <div class="stat-label">Emails Sent</div>
                </div>
                <div class="stat">
                    <div class="stat-value">{{ number_format($stats['opens']) }}</div>
                    <div class="stat-label">Opens ({{ $stats['open_rate'] }}%)</div>
                </div>
                <div class="stat">
                    <div class="stat-value">{{ number_format($stats['clicks']) }}</div>
                    <div class="stat-label">Clicks ({{ $stats['click_rate'] }}%)</div>
                </div>
                <div class="stat">
                    <div class="stat-value">{{ number_format($stats['new_subscribers']) }}</div>
                    <div class="stat-label">New Subscribers</div>
                </div>
                <div class="stat">
                    <div class="stat-value">{{ number_format($stats['subscribers']) }}</div>
                    <div class="stat-label">Total Subscribers</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} EchoMail. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
