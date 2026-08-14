<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Confirmed</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #e6f7ef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            max-width: 480px;
            width: 100%;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(26, 155, 127, 0.15);
            overflow: hidden;
            text-align: center;
        }
        .banner {
            background: linear-gradient(135deg, #2ECC71, #1A9B7F);
            padding: 40px 20px;
            color: white;
        }
        .logo {
            font-size: 26px;
            font-weight: bold;
            letter-spacing: -0.5px;
        }
        .icon {
            font-size: 56px;
            margin-top: 12px;
        }
        .body {
            padding: 40px 30px;
        }
        .title {
            font-size: 22px;
            color: #111827;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .message {
            font-size: 15px;
            color: #374151;
            line-height: 1.7;
        }
        .home-link {
            display: inline-block;
            margin-top: 24px;
            background: linear-gradient(135deg, #2ECC71, #1A9B7F);
            color: white;
            text-decoration: none;
            padding: 12px 32px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="banner">
            <div class="logo">Levelup Xperience</div>
            <div class="icon">✅</div>
        </div>
        <div class="body">
            <div class="title">Subscription Confirmed</div>
            <div class="message">{{ $message }}</div>
            <a href="{{ url('/') }}" class="home-link">Back to Home</a>
        </div>
    </div>
</body>
</html>
