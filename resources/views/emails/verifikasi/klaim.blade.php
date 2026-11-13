@extends('emails.layout')

@php
    $headline = match ($decision) {
        'approved' => 'Klaim Disetujui',
        'revoked' => 'Klaim Dicabut',
        default => 'Klaim Tidak Disetujui',
    };
@endphp

@section('title', $headline . ' ' . config('app.name', 'SEEKA'))
@section('preheader', 'Hasil verifikasi klaim barang temuan Anda di ' . config('app.name', 'SEEKA') . '.')

@section('notice')
    Email keputusan ini dikirim ke <strong style="color: #202124;">{{ $claim->claimant->email }}</strong>. Jika kamu tidak merasa mengajukan klaim ini, abaikan email ini.
@endsection

@section('content')
    <p style="margin: 0; text-align: center;">
        <img src="{{ config('mail.logo_url') ?: asset('images/seeka-mark.png') }}" width="52" height="52" alt="{{ config('app.name', 'SEEKA') }}" style="display: inline-block; width: 52px; height: 52px; border: 0; outline: none; text-decoration: none;">
    </p>

    <h1 class="headline" style="margin: 18px 0 8px; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 24px; font-weight: 600; line-height: 1.35; text-align: center; color: #202124;">
        {{ $headline }}
    </h1>

    <p style="margin: 0; text-align: center; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 13px; line-height: 1.5; color: #5F6368;">
        Barang: {{ $claim->foundItem->title }}
    </p>

    <div style="height: 1px; margin: 24px 0; background-color: #E8EAED; font-size: 1px; line-height: 1px;">&nbsp;</div>

    <p style="margin: 0; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.7; color: #3C4043;">
        Halo, {{ $claim->claimant->name }}.
    </p>

    @if ($decision === 'approved')
        <p style="margin: 12px 0 0; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.7; color: #3C4043;">
            Klaim Anda atas barang temuan <strong style="color: #202124;">{{ $claim->foundItem->title }}</strong> telah <strong style="color: #202124;">disetujui</strong> setelah petugas mencocokkan bukti kepemilikan Anda dengan ciri khusus barang.
        </p>
        <p style="margin: 12px 0 0; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.7; color: #3C4043;">
            Silakan hubungi petugas untuk proses serah terima barang.
        </p>
    @elseif ($decision === 'revoked')
        <p style="margin: 12px 0 0; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.7; color: #3C4043;">
            Klaim Anda atas barang temuan <strong style="color: #202124;">{{ $claim->foundItem->title }}</strong> <strong style="color: #202124;">dicabut</strong> karena pengajuan banding dari pihak lain atas barang ini diterima oleh petugas.
        </p>
        <p style="margin: 12px 0 0; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.7; color: #3C4043;">
            Jika Anda masih memiliki bukti kepemilikan yang lebih kuat, silakan hubungi petugas.
        </p>
    @else
        <p style="margin: 12px 0 0; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.7; color: #3C4043;">
            Setelah diperiksa, klaim Anda atas barang temuan <strong style="color: #202124;">{{ $claim->foundItem->title }}</strong> <strong style="color: #202124;">tidak dapat disetujui</strong>.
        </p>
    @endif

    @if (filled($adminNote))
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 20px;">
            <tr>
                <td class="notice-cell" style="padding: 12px 14px; background-color: #F8F9FA; border: 1px solid #DADCE0; border-radius: 8px; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 12px; line-height: 1.6; color: #5F6368;">
                    <strong style="color: #202124;">Catatan petugas:</strong> {{ $adminNote }}
                </td>
            </tr>
        </table>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top: 24px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td bgcolor="#921300" style="border-radius: 5px;">
                            <a href="{{ route('klaim.saya') }}" target="_blank" rel="noopener" class="button-link" style="display: inline-block; padding: 12px 24px; border-radius: 5px; background-color: #921300; color: #FFFFFF; font-family: 'Open Sans', 'Segoe UI', Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 600; line-height: 1.4; text-align: center; text-decoration: none;">
                                Lihat Klaim Saya
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection
