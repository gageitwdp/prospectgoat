<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $template->subject }}</title>
</head>
<body style="margin:0;padding:24px;background:#f7f8fa;color:#17212b;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:700px;margin:0 auto;background:#ffffff;border:1px solid #d9dee5;border-radius:10px;overflow:hidden;">
        <tr>
            <td style="padding:28px 28px 18px;">
                <p style="margin:0 0 12px;color:#5f6c7a;font-size:12px;letter-spacing:0.18em;text-transform:uppercase;">ProspectGoat</p>
                <h1 style="margin:0 0 12px;color:#1f2933;font-size:24px;line-height:1.2;">Inquiry Confirmation</h1>
                <div style="font-size:16px;line-height:1.75;color:#17212b;">
                    {!! $bodyHtml !!}
                </div>

                <div style="margin-top:22px;">
                    @include('emails.partials.signature')
                </div>
            </td>
        </tr>
    </table>
</body>
</html>