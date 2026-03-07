<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0; padding:0; background:#f2f5fb; font-family: Arial, Helvetica, sans-serif; color:#132238;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f2f5fb; padding:24px 10px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px; background:#ffffff; border-radius:14px; overflow:hidden; border:1px solid #d8e1f2;">
          <tr>
            <td style="padding:18px 24px; background:#0f2f57;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                  <td align="left" style="vertical-align:middle;">
                    <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" style="height:25px; width:auto; display:block;" />
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:24px;">
              <h1 style="margin:0 0 10px; font-size:24px; line-height:1.25; color:#173a67;">{{ $heading }}</h1>

              @if(!empty($intro))
              <p style="margin:0 0 16px; font-size:14px; line-height:1.65; color:#334e73;">{{ $intro }}</p>
              @endif

              @foreach($bodyLines as $line)
              <p style="margin:0 0 12px; font-size:14px; line-height:1.65; color:#203753;">{{ $line }}</p>
              @endforeach

              @if(!empty($ctaLabel) && !empty($ctaUrl))
              <table role="presentation" cellspacing="0" cellpadding="0" style="margin: 20px 0 6px;">
                <tr>
                  <td style="border-radius:8px; background:#0f2f57;">
                    <a href="{{ $ctaUrl }}" style="display:inline-block; padding:12px 18px; color:#ffffff; text-decoration:none; font-size:14px; font-weight:700;">{{ $ctaLabel }}</a>
                  </td>
                </tr>
              </table>
              @endif
            </td>
          </tr>

          <tr>
            <td style="padding:16px 24px 22px; background:#f8fbff; border-top:1px solid #e0e9f6;">
              <p style="margin:0; font-size:12px; line-height:1.5; color:#56708f;">
                {!! nl2br(e($footerNote ?: "Best regards,\nAlessio Battista\nMaccento Real Estate Media")) !!}
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
