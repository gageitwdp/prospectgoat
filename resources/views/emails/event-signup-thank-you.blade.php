<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thank you for attending our open house</title>
</head>
<body style="margin:0;padding:24px;background:#f7f8fa;color:#17212b;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:700px;margin:0 auto;background:#ffffff;border:1px solid #d9dee5;border-radius:10px;overflow:hidden;">
        <tr>
            <td style="padding:28px 28px 18px;">
                <p style="margin:0 0 12px;color:#5f6c7a;font-size:12px;letter-spacing:0.18em;text-transform:uppercase;">ProspectGoat</p>
                <h1 style="margin:0 0 12px;color:#1f2933;font-size:24px;line-height:1.2;">Thank You For Attending Our Open House</h1>

                <p style="margin:0 0 14px;color:#17212b;font-size:16px;line-height:1.7;">Hi {{ $firstName }},</p>
                <p style="margin:0 0 14px;color:#17212b;font-size:16px;line-height:1.7;">Just imagine, this could be your new home!</p>

                @if (! $isWorkingWithAgent)
                    <p style="margin:0 0 14px;color:#17212b;font-size:16px;line-height:1.7;">If you are not signed with an agent, I would love to work with you and help make your dream a reality.</p>
                @endif

                <p style="margin:0 0 14px;color:#17212b;font-size:16px;line-height:1.7;">The good news is you have already taken the first step by going to an open house.</p>
                <p style="margin:0 0 14px;color:#17212b;font-size:16px;line-height:1.7;">Most people wait until it is too late to take the next step.</p>
                <p style="margin:0 0 14px;color:#17212b;font-size:16px;line-height:1.7;">I am ready to answer your questions.</p>
                <p style="margin:0 0 22px;color:#17212b;font-size:16px;line-height:1.7;">Respond to this email, let me know a good time, and we can discuss in more detail what comes next.</p>

                @include('emails.partials.signature')
            </td>
        </tr>
    </table>
</body>
</html>