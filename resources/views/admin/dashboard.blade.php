@extends('layouts.app')

@section('title', 'Dashboard Admin — ' . config('app.name', 'SEEKA'))

@php
    $statCards = [
        ['label' => 'Pengguna', 'value' => $stats['users']],
        ['label' => 'Laporan Kehilangan', 'value' => $stats['lost']],
        ['label' => 'Laporan Penemuan', 'value' => $stats['found']],
        ['label' => 'Klaim Menunggu', 'value' => $stats['claims_pending']],
        ['label' => 'Banding Terbuka', 'value' => $stats['disputes_open']],
        ['label' => 'Serah Terima', 'value' => $stats['handovers']],
    ];

    $badgeClass = 'inline-flex shrink-0 items-center rounded-control border-[0.5px] px-2.5 py-0.5 text-caption font-semibold';

    $activityBadges = [
        'lost' => ['label' => 'Kehilangan', 'class' => 'bg-lost border-lost text-surface'],
        'found' => ['label' => 'Penemuan', 'class' => 'bg-found border-found text-surface'],
        'claim' => ['label' => 'Klaim', 'class' => 'bg-claimed border-claimed text-surface'],
        'dispute' => ['label' => 'Banding', 'class' => 'bg-open border-open text-surface'],
        'handover' => ['label' => 'Serah Terima', 'class' => 'bg-returned border-returned text-surface'],
    ];

    $statusRows = [
        ['key' => 'open', 'label' => 'Terbuka', 'color' => 'bg-open'],
        ['key' => 'claimed', 'label' => 'Diklaim', 'color' => 'bg-claimed'],
        ['key' => 'returned', 'label' => 'Dikembalikan', 'color' => 'bg-returned'],
        ['key' => 'closed', 'label' => 'Ditutup', 'color' => 'bg-closed'],
    ];

    $maxTrend = collect($trend)->max('count') ?? 0;
    $totalStatuses = max(1, (int) $statusCounts->sum());
@endphp

@section('content')
    <main class="mx-auto w-full max-w-7xl flex-1 px-5 pb-16 pt-6 sm:px-8">
        <h1 class="text-display font-display mb-[1.15em]">Dashboard Admin</h1>

        {{-- Kartu Statistik --}}
        <section class="mt-6">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                @foreach ($statCards as $card)
                    <div class="rounded-control border-[0.5px] border-line bg-white p-5">
                        <p class="text-body-sm text-muted">{{ $card['label'] }}</p>
                        <p class="mt-1 text-headline font-display text-ink">{{ $card['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Hal yang Menunggu Tindakan --}}
        <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
            <section>
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-title font-display">Klaim Menunggu</h2>

                    <a href="#" class="shrink-0 text-label text-primary transition hover:underline">Lihat semua</a>
                </div>

                <ul class="mt-4 divide-y divide-black/10 rounded-control border-[0.5px] border-line bg-white">
                    @forelse ($pendingClaims as $claim)
                        <li class="flex items-center gap-4 p-4">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-body font-semibold text-ink">
                                    {{ $claim->foundItem?->title ?? 'Barang temuan' }}
                                </p>
                                <p class="truncate text-body-sm text-muted">
                                    {{ $claim->claimant?->name ?? 'Pengguna' }} ·
                                    {{ $claim->created_at->locale('id')->translatedFormat('j F Y') }}
                                </p>
                            </div>

                            <span class="{{ $badgeClass }} bg-open border-open text-surface">Menunggu Verifikasi</span>
                        </li>
                    @empty
                        <li class="p-4 text-body-sm text-muted">Belum ada klaim yang menunggu verifikasi.</li>
                    @endforelse
                </ul>
            </section>

            <section>
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-title font-display">Banding Terbuka</h2>

                    <a href="#" class="shrink-0 text-label text-primary transition hover:underline">Lihat semua</a>
                </div>

                <ul class="mt-4 divide-y divide-black/10 rounded-control border-[0.5px] border-line bg-white">
                    @forelse ($openDisputes as $dispute)
                        <li class="flex items-center gap-4 p-4">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-body font-semibold text-ink">
                                    {{ $dispute->claim?->foundItem?->title ?? 'Barang temuan' }}
                                </p>
                                <p class="truncate text-body-sm text-muted">
                                    {{ $dispute->reportedBy?->name ?? 'Pengguna' }} ·
                                    {{ $dispute->created_at->locale('id')->translatedFormat('j F Y') }}
                                </p>
                            </div>

                            <span class="{{ $badgeClass }} bg-open border-open text-surface">Terbuka</span>
                        </li>
                    @empty
                        <li class="p-4 text-body-sm text-muted">Belum ada banding yang terbuka.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        {{-- Aktivitas Terbaru --}}
        <section class="mt-8">
            <h2 class="text-title font-display">Aktivitas Terbaru</h2>

            <ul class="mt-4 divide-y divide-black/10 rounded-control border-[0.5px] border-line bg-white">
                @forelse ($activities as $activity)
                    <li class="flex items-center gap-4 p-4">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-body font-semibold text-ink">{{ $activity['title'] }}</p>
                            <p class="truncate text-body-sm text-muted">
                                {{ $activity['subtitle'] }} ·
                                {{ $activity['at']->locale('id')->translatedFormat('j F Y') }}
                            </p>
                        </div>

                        <span class="{{ $badgeClass }} {{ $activityBadges[$activity['type']]['class'] }}">
                            {{ $activityBadges[$activity['type']]['label'] }}
                        </span>
                    </li>
                @empty
                    <li class="p-4 text-body-sm text-muted">Belum ada aktivitas.</li>
                @endforelse
            </ul>
        </section>

        {{-- Tren Laporan --}}
        <section class="mt-8">
            <h2 class="text-title font-display">Tren Laporan</h2>

            <div class="mt-4 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-control border-[0.5px] border-line bg-white p-5">
                    <h3 class="text-subtitle font-display">Laporan per Bulan</h3>
                    <p class="mt-1 text-caption text-muted">Enam bulan terakhir</p>

                    <div class="mt-5 flex items-end gap-2">
                        @foreach ($trend as $month)
                            <div class="flex flex-1 flex-col items-center gap-1.5">
                                <span class="text-caption text-muted">{{ $month['count'] }}</span>
                                <div class="flex h-28 w-full items-end">
                                    <div class="w-full rounded-control bg-primary"
                                        style="height: {{ $maxTrend > 0 ? round($month['count'] / $maxTrend * 100) : 0 }}%; min-height: 2px;"></div>
                                </div>
                                <span class="text-caption text-muted">{{ $month['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-control border-[0.5px] border-line bg-white p-5">
                    <h3 class="text-subtitle font-display">Laporan per Status</h3>
                    <p class="mt-1 text-caption text-muted">Distribusi seluruh laporan</p>

                    <ul class="mt-5 grid gap-4">
                        @foreach ($statusRows as $row)
                            @php
                                $count = (int) ($statusCounts[$row['key']] ?? 0);
                                $percent = (int) round($count / $totalStatuses * 100);
                            @endphp
                            <li>
                                <div class="flex items-center justify-between gap-3 text-body-sm">
                                    <span class="text-ink-soft">{{ $row['label'] }}</span>
                                    <span class="font-semibold text-ink">{{ $count }}</span>
                                </div>
                                <div class="mt-1.5 h-2 rounded-control bg-black/5">
                                    <div class="h-full rounded-control {{ $row['color'] }}" style="width: {{ $percent }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>
    </main>
@endsection
