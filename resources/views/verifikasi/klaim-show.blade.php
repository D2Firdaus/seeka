@extends('layouts.app')

@section('title', 'Detail Klaim — ' . config('app.name', 'SEEKA'))

@php
    $inputClass = 'w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-faint hover:border-black/40 focus:border-primary';
@endphp

@section('content')
    <main class="mx-auto w-full max-w-5xl flex-1 px-5 pb-16 pt-6 sm:px-8">
        <h1 class="text-display font-display mb-[1.15em]">Verifikasi Laporan</h1>

        <x-verifikasi-tabs active="klaim" />

        <a href="{{ route('admin.verifikasi.klaim') }}"
            class="text-caption font-semibold text-primary transition hover:underline">
            &larr; Kembali ke Antrean Klaim
        </a>

        @if ($errors->any())
            <x-alert type="error" class="mt-6">{{ $errors->first() }}</x-alert>
        @endif

        {{-- Info Barang Temuan --}}
        <section class="mt-5 rounded-control border-[0.5px] border-line bg-white p-5 sm:p-6">
            <h2 class="text-title font-display">Barang Temuan</h2>

            <div class="mt-4 flex flex-col gap-4 sm:flex-row">
                @if ($claim->foundItem->images->isNotEmpty())
                    <div class="flex shrink-0 flex-wrap gap-2">
                        @foreach ($claim->foundItem->images as $image)
                            <div class="size-20 overflow-hidden rounded-control bg-black/5">
                                <img src="{{ asset('storage/' . $image->path) }}" alt="{{ $claim->foundItem->title }}"
                                    class="h-full w-full object-cover" />
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <p class="text-body font-semibold text-ink">{{ $claim->foundItem->title }}</p>
                    <p class="mt-1 text-body-sm text-muted">
                        {{ $claim->foundItem->category->name }} ·
                        {{ $claim->foundItem->location->name }} ·
                        Ditemukan {{ $claim->foundItem->date_event->locale('id')->translatedFormat('j F Y') }}
                    </p>
                    <p class="mt-2 text-body-sm text-ink-soft">{{ $claim->foundItem->description }}</p>
                </div>
            </div>

            <div class="mt-4 rounded-control border-[0.5px] border-primary/40 bg-primary/5 p-4">
                <p class="text-label text-primary">Ciri Khusus Barang (Rahasia)</p>
                <p class="mt-1 text-body-sm text-ink">{{ $claim->foundItem->hidden_detail }}</p>
                <p class="mt-2 text-caption text-muted">
                    Cocokkan ciri khusus ini dengan bukti kepemilikan dari pemohon klaim sebelum mengambil keputusan.
                </p>
            </div>
        </section>

        {{-- Info Pemohon & Bukti Kepemilikan --}}
        <section class="mt-6 rounded-control border-[0.5px] border-line bg-white p-5 sm:p-6">
            <h2 class="text-title font-display">Bukti Kepemilikan</h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-label text-ink-soft">Pemohon Klaim</p>
                    <p class="mt-1 text-body text-ink">{{ $claim->claimant->name }}</p>
                    <p class="text-body-sm text-muted">{{ $claim->claimant->email }}</p>
                    @if (filled($claim->claimant->nim_nip))
                        <p class="text-body-sm text-muted">NIM/NIP: {{ $claim->claimant->nim_nip }}</p>
                    @endif
                </div>

                <div>
                    <p class="text-label text-ink-soft">Diajukan Pada</p>
                    <p class="mt-1 text-body text-ink">
                        {{ $claim->created_at->locale('id')->translatedFormat('j F Y, H:i') }}
                    </p>

                    @if ($claim->lostItem)
                        <p class="mt-2 text-label text-ink-soft">Laporan Kehilangan Terkait</p>
                        <p class="mt-1 text-body-sm text-ink">{{ $claim->lostItem->title }}</p>
                    @endif
                </div>
            </div>

            <div class="mt-4">
                <p class="text-label text-ink-soft">Deskripsi Bukti</p>
                <p class="mt-1 text-body-sm text-ink">{{ $claim->proof_description }}</p>
            </div>

            @if (filled($claim->proof_image))
                <div class="mt-4">
                    <p class="text-label text-ink-soft">Foto Bukti</p>
                    <div class="mt-2 max-w-xs overflow-hidden rounded-control bg-black/5">
                        <img src="{{ asset('storage/' . $claim->proof_image) }}" alt="Foto bukti kepemilikan"
                            class="w-full object-cover" />
                    </div>
                </div>
            @endif
        </section>

        {{-- Keputusan Verifikasi --}}
        <section class="mt-6 rounded-control border-[0.5px] border-line bg-white p-5 sm:p-6">
            <h2 class="text-title font-display">Keputusan Verifikasi</h2>

            @if ($claim->status === 'pending')
                <p class="mt-1 text-body-sm text-muted">
                    Setujui jika bukti kepemilikan cocok dengan ciri khusus barang, atau tolak beserta alasannya.
                </p>

                <form method="POST" action="{{ route('admin.verifikasi.klaim.tolak', $claim) }}" class="mt-5 grid gap-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="admin-note" class="block text-label text-ink-soft">Catatan Petugas</label>
                        <textarea id="admin-note" name="admin_note" rows="4"
                            placeholder="Tulis hasil pencocokan ciri khusus atau alasan penolakan..."
                            class="mt-1.5 resize-none {{ $inputClass }}">{{ old('admin_note') }}</textarea>
                        <p class="mt-1.5 text-caption text-muted">Wajib diisi saat menolak klaim.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit"
                            formaction="{{ route('admin.verifikasi.klaim.setujui', $claim) }}"
                            onclick="return confirm('Setujui klaim ini? Barang akan dikunci untuk klaim yang disetujui.')"
                            class="rounded-control bg-primary px-5 py-2.5 text-label text-white transition hover:bg-primary/90">
                            Setujui Klaim
                        </button>

                        <button type="submit"
                            onclick="return confirm('Tolak klaim ini? Pastikan catatan penolakan sudah diisi.')"
                            class="rounded-control border-[0.5px] border-primary px-5 py-2.5 text-label text-primary transition hover:bg-primary/5">
                            Tolak Klaim
                        </button>
                    </div>
                </form>
            @else
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-label text-ink-soft">Status</p>
                        <span @class([
                            'mt-1 inline-flex items-center rounded-control border-[0.5px] px-2.5 py-0.5 text-caption font-semibold',
                            'bg-returned border-returned text-surface' => $claim->status === 'approved',
                            'bg-primary border-primary text-surface' => $claim->status === 'rejected',
                        ])>
                            {{ $claim->status === 'approved' ? 'Disetujui' : 'Ditolak' }}
                        </span>
                    </div>

                    <div>
                        <p class="text-label text-ink-soft">Diverifikasi Oleh</p>
                        <p class="mt-1 text-body text-ink">{{ $claim->verifiedBy?->name ?? '—' }}</p>
                        @if ($claim->verified_at)
                            <p class="text-body-sm text-muted">
                                {{ $claim->verified_at->locale('id')->translatedFormat('j F Y, H:i') }}
                            </p>
                        @endif
                    </div>
                </div>

                @if (filled($claim->admin_note))
                    <div class="mt-4 rounded-control border-[0.5px] border-line bg-black/[0.02] p-4">
                        <p class="text-label text-ink-soft">Catatan Petugas</p>
                        <p class="mt-1 text-body-sm text-ink">{{ $claim->admin_note }}</p>
                    </div>
                @endif
            @endif
        </section>
    </main>
@endsection
