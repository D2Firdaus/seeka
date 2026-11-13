@php
    $headline = match ($decision) {
        'approved' => 'Klaim Disetujui',
        'revoked' => 'Klaim Dicabut',
        default => 'Klaim Tidak Disetujui',
    };
@endphp
Halo, {!! $claim->claimant->name !!}.

Email keputusan ini dikirim ke {!! $claim->claimant->email !!}. Jika kamu tidak merasa mengajukan klaim ini, abaikan email ini.

{!! $headline !!}

Barang: {!! $claim->foundItem->title !!}

@if ($decision === 'approved')
Klaim Anda atas barang temuan tersebut telah disetujui setelah petugas mencocokkan bukti kepemilikan Anda dengan ciri khusus barang. Silakan hubungi petugas untuk proses serah terima barang.
@elseif ($decision === 'revoked')
Klaim Anda atas barang temuan tersebut dicabut karena pengajuan banding dari pihak lain atas barang ini diterima oleh petugas. Jika Anda masih memiliki bukti kepemilikan yang lebih kuat, silakan hubungi petugas.
@else
Setelah diperiksa, klaim Anda atas barang temuan tersebut tidak dapat disetujui.
@endif

@if (filled($adminNote))
Catatan petugas: {!! $adminNote !!}

@endif
Lihat klaim Anda: {!! route('klaim.saya') !!}

Email ini dikirim otomatis oleh {!! config('app.name', 'SEEKA') !!}. Jangan balas email ini.

&copy; {{ date('Y') }} {!! config('app.name', 'SEEKA') !!}
