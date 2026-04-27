<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Notification</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f0f5;font-family:-apple-system,BlinkMacSystemFont,'SF Pro Display','Segoe UI',Helvetica,Arial,sans-serif;">

    <!-- Outer wrapper -->
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f0f0f5;padding:48px 16px;">
        <tr>
            <td align="center">

                <!-- Card -->
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:400px;background:#ffffff;border-radius:28px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.10);">

                    <!-- Top accent bar -->
                    <tr>
                        <td style="height:4px;background:linear-gradient(90deg,#34C759,#30A2FF);font-size:0;line-height:0;">&nbsp;</td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td align="center" style="padding:44px 36px 40px;">

                            <!-- Avatar -->
                            <div style="width:76px;height:76px;border-radius:50%;background:linear-gradient(135deg,#30A2FF,#1a7fd4);display:inline-block;text-align:center;line-height:76px;font-size:32px;font-weight:800;color:#ffffff;letter-spacing:-1px;box-shadow:0 8px 24px rgba(48,162,255,0.35);">
                                {{ strtoupper(substr($senderName, 0, 1)) }}
                            </div>

                            <!-- Name -->
                            <p style="margin:16px 0 4px;font-size:19px;font-weight:700;color:#0d0d0d;letter-spacing:-0.3px;">
                                {{ $senderName }}
                            </p>

                            <!-- Description -->
                            <p style="margin:0 0 40px;font-size:13.5px;font-weight:500;color:#a0a0a8;letter-spacing:0.1px;">
                                {{ $paymentDescription }}
                            </p>

                            <!-- Divider -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td style="height:1px;background:linear-gradient(90deg,transparent,#e8e8ee,transparent);font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>

                            <!-- Amount block -->
                            @php
                                $raw = preg_replace('/[^0-9.]/', '', $amount);
                                $formatted = '$' . number_format((float) $raw, 2);
                            @endphp
                            <p style="margin:36px 0 0;font-size:54px;font-weight:800;color:#d1d1d6;letter-spacing:-2px;line-height:1;">
                                {{ $formatted }}
                            </p>

                            <!-- Sub description -->
                            <p style="margin:14px 0 4px;font-size:14px;font-weight:500;color:#a0a0a8;">
                                {{ $subDescription }}
                            </p>

                            <!-- Datetime -->
                            <p style="margin:0 0 40px;font-size:12.5px;font-weight:400;color:#b8b8c0;letter-spacing:0.2px;">
                                {{ $datetime }}
                            </p>

                            <!-- Status button -->
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td align="center" style="border-radius:50px;background-color:{{ $statusColor }};box-shadow:0 6px 20px {{ $statusColor }}55;">
                                        <span style="display:block;padding:17px 24px;font-size:16px;font-weight:700;color:#ffffff;letter-spacing:0.2px;">
                                            {{ $status }}
                                        </span>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                </table>
                <!-- /Card -->

            </td>
        </tr>
    </table>

</body>
</html>
