<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#18181b;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:24px 12px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;border:1px solid #e4e4e7;overflow:hidden;">
                    <tr>
                        <td style="padding:24px 28px 8px;font-size:20px;font-weight:700;color:#2563eb;">
                            {{ config('app.name', 'CARMAXING') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 28px;font-size:15px;line-height:1.6;color:#3f3f46;">
                            @yield('body')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 24px;border-top:1px solid #e4e4e7;font-size:12px;color:#71717a;">
                            &copy; {{ date('Y') }} {{ config('app.name', 'CARMAXING') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>