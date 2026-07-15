<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lezin Properties Email Test</title>
</head>
<body style="margin:0;padding:24px;background:#f7f8fa;color:#17212b;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #d9dee5;border-radius:8px;">
        <tr>
            <td style="padding:24px;">
                <h1 style="margin:0 0 12px;color:#1f2933;font-size:22px;">Email Test Successful</h1>
                <p style="margin:0 0 10px;line-height:1.5;">This is a test email sent from the admin profile email test tool.</p>
                <p style="margin:0 0 10px;line-height:1.5;"><strong>Destination:</strong> {{ $recipientEmail }}</p>
                <p style="margin:0 0 10px;line-height:1.5;"><strong>Requested by:</strong> {{ $requestedByName }}</p>
                <p style="margin:0 0 10px;line-height:1.5;"><strong>Delivery mode:</strong> {{ $deliveryMode }}</p>
                <p style="margin:0 0 10px;line-height:1.5;"><strong>Generated at:</strong> {{ $sentAt }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
