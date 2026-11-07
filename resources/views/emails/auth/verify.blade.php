@extends('emails.layout')

@section('title', 'Aktivasi Akun ' . config('app.name', 'SEEKA'))
@section('preheader', 'Aktifkan akun SEEKA kamu untuk mulai membantu barang kembali kepada pemiliknya.')

@section('content')
    <p style="margin: 0; text-align: center;">
        <img src="{{ config('mail.logo_url') ?: asset('images/seeka-mark.png') }}" width="52" height="52" alt="{{ config('app.name', 'SEEKA') }}" style="display: inline-block; width: 52px; height: 52px; border: 0; outline: none; text-decoration: none;">
    </p>

    <h1 class="headline" style="margin: 18px 0 8px; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 24px; font-weight: 600; line-height: 1.35; text-align: center; color: #202124;">
        Aktivasi Akun {{ config('app.name', 'SEEKA') }}
    </h1>

    <p style="margin: 0; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 13px; line-height: 1.5; text-align: center; color: #5F6368;">
        dikirim ke {{ $email }}
    </p>

    <div style="height: 1px; margin: 24px 0; background-color: #E8EAED; font-size: 1px; line-height: 1px;">&nbsp;</div>

    <p style="margin: 0; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.7; color: #3C4043;">
        Halo, {{ $name }}.
    </p>

    <p style="margin: 12px 0 0; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.7; color: #3C4043;">
        Terima kasih sudah mendaftar di <strong style="color: #202124;">{{ config('app.name', 'SEEKA') }}</strong>. Klik tombol di bawah untuk mengaktifkan akun kamu dan mulai membantu barang kembali kepada pemiliknya.
    </p>

    <p style="margin: 12px 0 0; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.7; color: #3C4043;">
        Link ini berlaku {{ $expiresInMinutes }} menit.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 24px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td bgcolor="#921300" style="border-radius: 5px;">
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="button-link" style="display: inline-block; padding: 12px 24px; border-radius: 5px; background-color: #921300; color: #FFFFFF; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 600; line-height: 1.4; text-align: center; text-decoration: none;">
                                Aktifkan Akun
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin: 20px 0 0; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 12px; line-height: 1.6; color: #5F6368;">
        Jika tombol tidak berfungsi, salin tautan berikut:
        <br>
        <a href="{{ $url }}" target="_blank" rel="noopener" style="font-size: 11px; color: #80868B; text-decoration: underline; word-break: break-all;">{{ $url }}</a>
    </p>
@endsection
