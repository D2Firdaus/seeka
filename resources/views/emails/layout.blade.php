<!DOCTYPE html>
<html lang="id" xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>@yield('title', config('app.name', 'SEEKA'))</title>

    <style>
        @media only screen and (max-width: 620px) {
            .email-shell {
                padding: 24px 12px !important;
            }

            .content-cell {
                padding: 28px 24px !important;
            }

            .notice-cell {
                padding: 12px 14px !important;
            }

            .headline {
                font-size: 22px !important;
            }

            .button-link {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
        }
    </style>
</head>

<body style="margin: 0; padding: 0; width: 100%; background-color: #FFFFFF; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    <span style="display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0; max-height: 0; max-width: 0; overflow: hidden; mso-hide: all;">@yield('preheader', 'Aktifkan akun SEEKA kamu untuk mulai membantu barang kembali kepada pemiliknya.')</span>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FFFFFF;">
        <tr>
            <td align="center" class="email-shell" style="padding: 32px 16px 24px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 520px; width: 100%; table-layout: fixed;">

                    <tr>
                        <td style="padding-bottom: 16px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="table-layout: fixed;">
                                <tr>
                                    <td class="notice-cell" style="padding: 14px 18px; background-color: #F8F9FA; border: 1px solid #DADCE0; border-radius: 8px; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 12px; line-height: 1.6; color: #5F6368;">
                                        @hasSection('notice')
                                            @yield('notice')
                                        @else
                                            Email aktivasi ini dikirim ke <strong style="color: #202124;">{{ $email }}</strong>. Jika kamu tidak merasa membuat akun, abaikan email ini.
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #FFFFFF; border: 1px solid #DADCE0; border-radius: 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="table-layout: fixed;">
                                <tr>
                                    <td class="content-cell" style="padding: 40px 40px 36px;">
                                        @yield('content')
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 20px 8px 0; text-align: center;">
                            <p style="margin: 0; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 12px; line-height: 1.7; color: #5F6368;">
                                Email ini dikirim otomatis oleh {{ config('app.name', 'SEEKA') }}. Jangan balas email ini.
                            </p>
                            <p style="margin: 4px 0 0; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 11px; line-height: 1.7; color: #80868B;">
                                &copy; {{ date('Y') }} {{ config('app.name', 'SEEKA') }}
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
