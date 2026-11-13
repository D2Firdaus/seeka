@php
    $headline = $decision === 'resolved' ? 'Banding Diterima' : 'Banding Ditolak';
@endphp
Halo, {!! $dispute->reportedBy->name !!}.

Email keputusan ini dikirim ke {!! $dispute->reportedBy->email !!}. Jika kamu tidak merasa mengajukan banding ini, abaikan email ini.

{!! $headline !!}

Barang: {!! $dispute->claim->foundItem->title !!}

@if ($decision === 'resolved')
Pengajuan banding Anda atas barang temuan tersebut telah diterima. Klaim terdahulu atas barang ini dicabut dan status barang kembali terbuka. Silakan ajukan klaim Anda atas barang ini untuk memulai proses serah terima.

Ajukan klaim: {!! route('klaim.create', $dispute->claim->foundItem) !!}
@else
Setelah diperiksa, pengajuan banding Anda atas barang temuan tersebut tidak dapat diterima. Keputusan atas klaim yang berlaku tetap berlaku.

Lihat beranda: {!! route('home') !!}
@endif

@if (filled($resolutionNote))
Catatan petugas: {!! $resolutionNote !!}

@endif
Email ini dikirim otomatis oleh {!! config('app.name', 'SEEKA') !!}. Jangan balas email ini.

&copy; {{ date('Y') }} {!! config('app.name', 'SEEKA') !!}
