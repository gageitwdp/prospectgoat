<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Mortgage Calculator Results</title>
</head>
<body style="margin:0;padding:24px;background:#f7f8fa;color:#17212b;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:700px;margin:0 auto;background:#ffffff;border:1px solid #d9dee5;border-radius:10px;overflow:hidden;">
        <tr>
            <td style="padding:28px 28px 18px;">
                <p style="margin:0 0 12px;color:#5f6c7a;font-size:12px;letter-spacing:0.18em;text-transform:uppercase;">ProspectGoat</p>
                <h1 style="margin:0 0 12px;color:#1f2933;font-size:24px;line-height:1.2;">Mortgage Calculator Results</h1>
                <p style="margin:0 0 16px;color:#17212b;font-size:15px;line-height:1.6;">Hi {{ $fullName }}, here are the results you requested.</p>

                <h2 style="margin:0 0 8px;color:#1f2933;font-size:18px;">Estimated Monthly Payment</h2>
                <p style="margin:0 0 16px;color:#1e3a5f;font-size:28px;font-weight:700;">${{ number_format((float) $results['total_monthly_payment'], 2) }}</p>

                <h3 style="margin:0 0 8px;color:#1f2933;font-size:16px;">Payment Breakdown</h3>
                <p style="margin:0 0 6px;line-height:1.5;"><strong>Principal & Interest:</strong> ${{ number_format((float) $results['principal_interest_monthly'], 2) }}</p>
                <p style="margin:0 0 6px;line-height:1.5;"><strong>Property Tax:</strong> ${{ number_format((float) $results['property_tax_monthly'], 2) }}</p>
                <p style="margin:0 0 6px;line-height:1.5;"><strong>Home Insurance:</strong> ${{ number_format((float) $results['home_insurance_monthly'], 2) }}</p>
                <p style="margin:0 0 6px;line-height:1.5;"><strong>HOA:</strong> ${{ number_format((float) $results['hoa_monthly'], 2) }}</p>
                <p style="margin:0 0 16px;line-height:1.5;"><strong>PMI:</strong> ${{ number_format((float) $results['pmi_monthly'], 2) }}</p>

                <h3 style="margin:0 0 8px;color:#1f2933;font-size:16px;">Inputs</h3>
                <p style="margin:0 0 6px;line-height:1.5;"><strong>Home Price:</strong> ${{ number_format((float) $inputs['home_price'], 2) }}</p>
                <p style="margin:0 0 6px;line-height:1.5;"><strong>Down Payment:</strong> ${{ number_format((float) $inputs['down_payment'], 2) }}</p>
                <p style="margin:0 0 6px;line-height:1.5;"><strong>Interest Rate:</strong> {{ number_format((float) $inputs['annual_interest_rate'], 3) }}%</p>
                <p style="margin:0 0 6px;line-height:1.5;"><strong>Loan Term:</strong> {{ (int) $inputs['loan_term_years'] }} years</p>
                <p style="margin:0 0 6px;line-height:1.5;"><strong>Property Tax Rate:</strong> {{ number_format((float) $inputs['property_tax_rate'], 3) }}%</p>
                <p style="margin:0 0 6px;line-height:1.5;"><strong>Yearly Insurance:</strong> ${{ number_format((float) $inputs['home_insurance_yearly'], 2) }}</p>
                <p style="margin:0 0 6px;line-height:1.5;"><strong>Monthly HOA:</strong> ${{ number_format((float) $inputs['hoa_monthly'], 2) }}</p>
                <p style="margin:0 0 0;line-height:1.5;"><strong>Monthly PMI:</strong> ${{ number_format((float) $inputs['pmi_monthly'], 2) }}</p>

                <div style="margin-top:22px;">
                    @include('emails.partials.signature')
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
