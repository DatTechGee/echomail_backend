<!DOCTYPE html>
<html>
<head>
    <title>{{ $campaign->subject }}</title>
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
            padding: 20px 30px;
            text-align: center;
            color: white;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .content {
            padding: 30px;
            background-color: #ffffff;
        }
        .footer {
            background: #f8fafc;
            padding: 20px 30px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }

        /* Rich text editor styles */
        .content h1, .content h2, .content h3 { margin-top: 1.5em; margin-bottom: 0.5em; font-weight: 600; }
        .content h1 { font-size: 28px; }
        .content h2 { font-size: 24px; }
        .content h3 { font-size: 20px; }
        .content p { margin-bottom: 1em; }
        .content img { max-width: 100%; height: auto; border-radius: 8px; }
        .content ul, .content ol { margin-bottom: 1em; padding-left: 20px; }
        .content li { margin-bottom: 0.5em; }
        .content a { color: #3b82f6; text-decoration: none; }
        .content a:hover { text-decoration: underline; }
        .content code { background-color: #f3f4f6; padding: 2px 4px; border-radius: 4px; font-family: monospace; font-size: 0.9em; }
        .content strong { font-weight: 600; }
        .content em { font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">EchoMail</div>
            <div>Email Campaign Management</div>
        </div>

        <div class="content">
            {!! $campaign->html_content !!}
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} EchoMail. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
