@extends('layouts.app')

@section('title', 'Detail Banding — ' . config('app.name', 'SEEKA'))

@php
    $inputClass = 'w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-faint hover:border-black/40 focus:border-primary';
@endphp

@section('content')
    <main class="mx-auto w-full max-w-5xl flex-1 px-5 pb-16 pt-6 sm:px-8">
        <h1 class="text-display font-display mb-[1.15em]">Verifikasi Laporan</h1>

        <x-verifikasi-tabs active="banding" />

        <a href="{{ route('admin.verifikasi.banding') }}"
            class="text-caption font-semibold text-primary transition hover:underline">
            &larr; Kembali ke Antrean Banding
        </a>

        @if ($errors->any())
            <x-alert type="error" class="mt-6">{{ $errors->first() }}</x-alert>
        @endif

        {{-- Info Barang Temuan --}}
        <section class="mt-5 rounded-control border-[0.5px] border-line bg-white p-5 sm:p-6">
            <h2 class="text-title font-display">Barang Temuan</h2>

            <div class="mt-4 flex flex-col gap-4 sm:flex-row">
                @if ($dispute->claim->foundItem->images->isNotEmpty())
                    <div class="flex shrink-0 flex-wrap gap-2">
                        @foreach ($dispute->claim->foundItem->images as $image)
                            <div class="size-20 overflow-hidden rounded-control bg-black/5">
                                <img src="{{ asset('storage/' . $image->path) }}" alt="{{ $dispute->claim->foundItem->title }}"
                                    class="h-full w-full object-cover" />
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <p class="text-body font-semibold text-ink">{{ $dispute->claim->foundItem->title }}</p>
                    <p class="mt-1 text-body-sm text-muted">
                        {{ $dispute->claim->foundItem->category->name }} ·
                        {{ $dispute->claim->foundItem->location->name }} ·
                        Ditemukan {{ $dispute->claim->foundItem->date_event->locale('id')->translatedFormat('j F Y') }}
                    </p>
                    <p class="mt-2 text-body-sm text-ink-soft">{{ $dispute->claim->foundItem->description }}</p>
                </div>
            </div>

            <div class="mt-4 rounded-control border-[0.5px] border-primary/40 bg-primary/5 p-4">
                <p class="text-label text-primary">Ciri Khusus Barang (Rahasia)</p>
                <p class="mt-1 text-body-sm text-ink">{{ $dispute->claim->foundItem->hidden_detail }}</p>
            </div>
        </section>

        {{-- Klaim yang Dibandingkan --}}
        <section class="mt-6 rounded-control border-[0.5px] border-line bg-white p-5 sm:p-6">
            <h2 class="text-title font-display">Klaim yang Dibandingkan</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-label text-ink-soft">Pemilik Klaim</p>
                    <p class="mt-1 text-body text-ink">{{ $dispute->claim->claimant->name }}</p>
                    <p class="text-body-sm text-muted">{{ $dispute->claim->claimant->email }}</p>
                </div>

                <div>
                    <p class="text-label text-ink-soft">Status Klaim</p>
                    <span @class([
                        'mt-1 inline-flex items-center rounded-control border-[0.5px] px-2.5 py-0.5 text-caption font-semibold',
                        'bg-returned border-returned text-surface' => $dispute->claim->status === 'approved',
                        'bg-primary border-primary text-surface' => $dispute->claim->status === 'rejected',
                        'bg-open border-open text-surface' => $dispute->claim->status === 'pending',
                    ])>
                        {{ match ($dispute->claim->status) {
                            'approved' => 'Disetujui',
                            'rejected' => 'Ditolak',
                            default => 'Menunggu',
                        } }}
                    </span>
                    @if ($dispute->claim->verified_at)
                        <p class="mt-1 text-body-sm text-muted">
                            Diverifikasi {{ $dispute->claim->verified_at->locale('id')->translatedFormat('j F Y') }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="mt-4">
                <p class="text-label text-ink-soft">Deskripsi Bukti Kepemilikan</p>
                <p class="mt-1 text-body-sm text-ink">{{ $dispute->claim->proof_description }}</p>
            </div>

            @if (filled($dispute->claim->proof_image))
                <div class="mt-4">
                    <p class="text-label text-ink-soft">Foto Bukti</p>
                    <div class="mt-2 max-w-xs overflow-hidden rounded-control bg-black/5">
                        <img src="{{ asset('storage/' . $dispute->claim->proof_image) }}" alt="Foto bukti kepemilikan"
                            class="w-full object-cover" />
                    </div>
                </div>
            @endif
        </section>

        {{-- Alasan Banding --}}
        <section class="mt-6 rounded-control border-[0.5px] border-line bg-white p-5 sm:p-6">
            <h2 class="text-title font-display">Alasan Banding</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-label text-ink-soft">Pengaju Banding</p>
                    <p class="mt-1 text-body text-ink">{{ $dispute->reportedBy->name }}</p>
                    <p class="text-body-sm text-muted">{{ $dispute->reportedBy->email }}</p>
                </div>

                <div>
                    <p class="text-label text-ink-soft">Diajukan Pada</p>
                    <p class="mt-1 text-body text-ink">
                        {{ $dispute->created_at->locale('id')->translatedFormat('j F Y, H:i') }}
                    </p>
                </div>
            </div>

            <div class="mt-4">
                <p class="text-label text-ink-soft">Uraian Alasan</p>
                <p class="mt-1 text-body-sm text-ink">{{ $dispute->reason }}</p>
            </div>
        </section>

        {{-- Keputusan Banding --}}
        <section class="mt-6 rounded-control border-[0.5px] border-line bg-white p-5 sm:p-6">
            <h2 class="text-title font-display">Keputusan Banding</h2>

            @if ($dispute->status === 'open')
                <p class="mt-1 text-body-sm text-muted">
                    Terima banding jika pengaju terbukti sebagai pemilik yang berhak, atau tolak beserta alasannya.
                </p>

                <form method="POST" action="{{ route('admin.verifikasi.banding.tolak', $dispute) }}" class="mt-5 grid gap-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="resolution-note" class="block text-label text-ink-soft">Catatan Petugas</label>
                        <textarea id="resolution-note" name="resolution_note" rows="4"
                            placeholder="Tulis hasil pemeriksaan banding atau alasan penolakan..."
                            class="mt-1.5 resize-none {{ $inputClass }}">{{ old('resolution_note') }}</textarea>
                        <p class="mt-1.5 text-caption text-muted">Wajib diisi saat menolak banding.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit"
                            formaction="{{ route('admin.verifikasi.banding.terima', $dispute) }}"
                            onclick="return confirm('Terima banding ini? Klaim terdahulu akan dicabut dan status barang kembali terbuka.')"
                            class="rounded-control bg-primary px-5 py-2.5 text-label text-white transition hover:bg-primary/90">
                            Terima Banding
                        </button>

                        <button type="submit"
                            onclick="return confirm('Tolak banding ini? Pastikan catatan penolakan sudah diisi.')"
                            class="rounded-control border-[0.5px] border-primary px-5 py-2.5 text-label text-primary transition hover:bg-primary/5">
                            Tolak Banding
                        </button>
                    </div>
                </form>
            @else
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-label text-ink-soft">Status</p>
                        <span @class([
                            'mt-1 inline-flex items-center rounded-control border-[0.5px] px-2.5 py-0.5 text-caption font-semibold',
                            'bg-returned border-returned text-surface' => $dispute->status === 'resolved',
                            'bg-primary border-primary text-surface' => $dispute->status === 'dismissed',
                        ])>
                            {{ $dispute->status === 'resolved' ? 'Diterima' : 'Ditolak' }}
                        </span>
                    </div>

                    <div>
                        <p class="text-label text-ink-soft">Diputuskan Oleh</p>
                        <p class="mt-1 text-body text-ink">{{ $dispute->resolvedBy?->name ?? '—' }}</p>
                        @if ($dispute->resolved_at)
                            <p class="text-body-sm text-muted">
                                {{ $dispute->resolved_at->locale('id')->translatedFormat('j F Y, H:i') }}
                            </p>
                        @endif
                    </div>
                </div>

                @if (filled($dispute->resolution_note))
                    <div class="mt-4 rounded-control border-[0.5px] border-line bg-black/[0.02] p-4">
                        <p class="text-label text-ink-soft">Catatan Petugas</p>
                        <p class="mt-1 text-body-sm text-ink">{{ $dispute->resolution_note }}</p>
                    </div>
                @endif
            @endif
        </section>
    </main>
@endsection
