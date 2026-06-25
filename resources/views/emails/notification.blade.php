<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0;background:#f4f4f5;color:#18181b;font-family:Arial,sans-serif;">
    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border:1px solid #e4e4e7;border-radius:10px;padding:28px;">
            <h1 style="margin:0 0 20px;font-size:22px;line-height:1.3;">{{ $subject }}</h1>
            <div style="white-space:pre-wrap;font-size:15px;line-height:1.65;">{{ $body }}</div>

            @if ($actionUrl && $actionLabel)
                <p style="margin:28px 0 0;">
                    <a
                        href="{{ $actionUrl }}"
                        style="display:inline-block;padding:12px 18px;border-radius:7px;background:#18181b;color:#ffffff;text-decoration:none;font-weight:600;"
                    >{{ $actionLabel }}</a>
                </p>
            @endif
        </div>
    </div>
</body>
</html>
