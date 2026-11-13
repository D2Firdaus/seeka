@extends('layouts.app')

@section('title', 'Verifikasi Laporan — ' . config('app.name', 'SEEKA'))

@php
    $statusOptions = [
        'all' => 'Semua Status',
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];
@endphp

@section('content')
    <main class="mx-auto w-full max-w-7xl flex-1 px-5 pb-16 pt-6 sm:px-8">
        <h1 class="text-display font-display mb-[1.15em]">Verifikasi Laporan</h1>

        <x-verifikasi-tabs active="klaim" />

        {{-- Form Pencarian & Filter --}}
        <form method="GET" action="{{ route('admin.verifikasi.klaim') }}" class="mb-5">
            <div class="flex flex-wrap items-stretch gap-3">
                <div class="flex h-11 min-w-0 flex-1 items-stretch lg:h-[3.125rem]">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul barang..."
                        class="w-full min-w-0 rounded-l-control border-[0.5px] border-r-0 border-line bg-white px-3.5 py-3 text-body outline-none transition hover:border-black/40 focus:border-primary" />

                    <button type="submit"
                        class="flex shrink-0 items-center justify-center gap-2 rounded-r-control bg-primary px-5 py-3 text-label text-white transition hover:bg-primary/90">
                        <x-icons.search class="h-5 w-5" />
                        <span class="hidden sm:inline">Cari</span>
                    </button>
                </div>

                <div class="relative flex h-11 shrink-0 items-stretch lg:h-[3.125rem]">
                    <select name="status" onchange="this.form.submit()"
                        class="h-full appearance-none rounded-control bg-primary py-2.5 pl-4 pr-10 text-label text-white transition hover:bg-primary/90">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" class="bg-white text-ink" @selected(request('status', 'pending') == $value)>
                                {{ $label }}</option>
                        @endforeach
                    </select>

                    <x-icons.chevron-right
                        class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-white" />
                </div>
            </div>
        </form>

        {{-- Status Filter Aktif & Jumlah Data --}}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 text-body-sm text-muted">
            <p>
                Menampilkan {{ $claims->firstItem() ?? 0 }}–{{ $claims->lastItem() ?? 0 }} dari {{ $claims->total() }} klaim
            </p>

            @if (request()->hasAny(['q', 'status']))
                <a href="{{ route('admin.verifikasi.klaim') }}" class="text-caption font-semibold text-primary transition hover:underline">
                    Reset Filter
                </a>
            @endif
        </div>

        {{-- List Klaim --}}
        <ul class="divide-y divide-black/10 rounded-control border-[0.5px] border-line bg-white">
            @forelse ($claims as $claim)
                <li class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:gap-4 transition hover:bg-black/[0.015]">
                    {{-- Info Barang & Pemohon Klaim --}}
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="size-12 shrink-0 overflow-hidden rounded-control bg-black/5">
                            @if ($claim->foundItem->images->isNotEmpty())
                                <img src="{{ asset('storage/' . $claim->foundItem->images->first()->path) }}"
                                    alt="{{ $claim->foundItem->title }}" class="h-full w-full object-cover" />
                            @else
                                <div class="flex h-full w-full items-center justify-center text-muted">
                                    <x-icons.book class="h-5 w-5 opacity-40" />
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-body font-semibold text-ink">{{ $claim->foundItem->title }}</p>
                            <p class="truncate text-body-sm text-muted">
                                Diklaim {{ $claim->claimant->name }} ·
                                {{ $claim->created_at->locale('id')->translatedFormat('j F Y') }}
                            </p>
                        </div>
                    </div>

                    {{-- Badges & Tombol Aksi --}}
                    <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 sm:justify-end">
                        <span @class([
                            'inline-flex items-center rounded-control border-[0.5px] px-2.5 py-0.5 text-caption font-semibold',
                            'bg-open border-open text-surface' => $claim->status === 'pending',
                            'bg-returned border-returned text-surface' => $claim->status === 'approved',
                            'bg-primary border-primary text-surface' => $claim->status === 'rejected',
                        ])>
                            {{ match ($claim->status) {
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                                default => 'Menunggu',
                            } }}
                        </span>

                        <a href="{{ route('admin.verifikasi.klaim.show', $claim) }}"
                            class="flex h-8 items-center justify-center gap-1.5 rounded-control border-[0.5px] border-line px-3 text-caption font-semibold text-primary transition hover:bg-primary/5"
                            title="{{ $claim->status === 'pending' ? 'Periksa Klaim' : 'Lihat Detail Klaim' }}"
                            aria-label="{{ $claim->status === 'pending' ? 'Periksa Klaim' : 'Lihat Detail Klaim' }}">
                            {{ $claim->status === 'pending' ? 'Periksa' : 'Detail' }}
                            <x-icons.chevron-right class="h-4 w-4" />
                        </a>
                    </div>
                </li>
            @empty
                <li class="p-8 text-center text-body text-muted">
                    @if (request()->hasAny(['q', 'status']))
                        Belum ada klaim yang cocok dengan filter yang dipilih.
                    @else
                        Belum ada klaim yang masuk untuk diverifikasi.
                    @endif
                </li>
            @endforelse
        </ul>

        {{-- Pagination --}}
        @if ($claims->hasPages())
            <div class="mt-6">
                {{ $claims->links('pagination.compact') }}
            </div>
        @endif
    </main>
@endsection
