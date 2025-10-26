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
            background-color: #0066FF;
        }
        .outer-wrapper {
            background-color: #0066FF;
            padding: 20px 20px 30px 20px;
        }
        .logo-section {
            text-align: center;
            padding: 20px 20px 15px 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #ffffff;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .content {
            padding: 40px 30px;
            background-color: #ffffff;
        }
        .footer {
            background-color: #ffffff;
            padding: 20px 30px;
            text-align: center;
            color: #6b7280;
            font-size: 13px;
            border-top: 1px solid #e5e7eb;
        }

        /* Rich text editor styles */
        .content h1, .content h2, .content h3 {
            margin-top: 1.5em;
            margin-bottom: 0.5em;
            font-weight: 600;
            color: #1f2937;
        }
        .content h1 { font-size: 28px; }
        .content h2 { font-size: 24px; }
        .content h3 { font-size: 20px; }
        .content p { margin-bottom: 1em; }
        .content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            display: block;
            margin: 1em 0;
        }
        .content ul, .content ol { margin-bottom: 1em; padding-left: 20px; }
        .content li { margin-bottom: 0.5em; }
        .content a { color: #0066FF; text-decoration: none; font-weight: 500; }
        .content a:hover { text-decoration: underline; }
        .content code {
            background-color: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
        }
        .content strong { font-weight: 600; }
        .content em { font-style: italic; }

        /* PDF embed styles */
        .content iframe[src*=".pdf"],
        .content embed[src*=".pdf"] {
            width: 100%;
            min-height: 500px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin: 1em 0;
        }

        /* PDF link/attachment styles */
        .pdf-attachment {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin: 1em 0;
            text-decoration: none;
            color: #374151;
            transition: background-color 0.2s;
        }
        .pdf-attachment:hover {
            background-color: #f3f4f6;
        }
        .pdf-icon {
            width: 40px;
            height: 40px;
            background-color: #dc2626;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            margin-right: 12px;
        }
        .pdf-info {
            flex: 1;
        }
        .pdf-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 2px;
        }
        .pdf-details {
            font-size: 13px;
            color: #6b7280;
        }

        /* Responsive */
        @media only screen and (max-width: 640px) {
            .outer-wrapper {
                padding: 15px 10px 20px 10px;
            }
            .logo-section {
                padding: 15px 20px 10px 20px;
            }
            .content {
                padding: 30px 20px;
            }
            .content iframe[src*=".pdf"],
            .content embed[src*=".pdf"] {
                min-height: 400px;
            }
        }
    </style>
</head>
<body>
    <div class="outer-wrapper">
        <div class="logo-section">
            <div class="logo">Coinbase</div>
        </div>

        <div class="container">
            <div class="content">
                {!! $htmlContent !!}
            </div>

            <div class="footer">
                <p>&copy; {{ date('Y') }} Coinbase. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
