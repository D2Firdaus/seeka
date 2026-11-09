@props(['item', 'toggleId', 'showAction' => true])

@php
    $images = $item->images;
    $firstImage = $images->first();
    $typeLabel = $item->type === 'lost' ? 'Kehilangan' : 'Penemuan';
    $typeBadgeClass = $item->type === 'lost'
        ? 'bg-lost text-surface border-lost'
        : 'bg-found text-surface border-found';

    $statusLabel = match ($item->status) {
        'claimed' => 'Diklaim',
        'returned' => 'Dikembalikan',
        'closed' => 'Ditutup',
        default => 'Terbuka',
    };
    $statusBadgeClass = match ($item->status) {
        'claimed' => 'bg-claimed text-surface border-claimed',
        'returned' => 'bg-returned text-surface border-returned',
        'closed' => 'bg-closed text-surface border-closed',
        default => 'bg-open text-surface border-open',
    };

    $isClosed = $item->status === 'closed';
    $isResolved = in_array($item->status, ['claimed', 'returned'], true);

    if ($isClosed) {
        $ctaLabel = 'Laporan Ditutup';
        $ctaHelper = 'Laporan ini telah ditutup';
        $ctaUrl = '#';
    } elseif ($isResolved) {
        $ctaLabel = 'Ajukan Banding';
        $ctaHelper = 'Merasa ini barang Anda?';
        $ctaUrl = auth()->check() ? '#' : route('login');
    } elseif ($item->type === 'lost') {
        $ctaLabel = 'Ajukan Penemuan';
        $ctaHelper = 'Menemukan Barang Ini?';
        $ctaUrl = auth()->check() ? route('penemuan.create', $item) : route('login');
    } else {
        $ctaLabel = 'Ajukan Klaim';
        $ctaHelper = 'Ini Barang Anda?';
        $ctaUrl = auth()->check() ? route('klaim.create', $item) : route('login');
    }

    $dateLabel = $item->type === 'lost' ? 'Tanggal Kehilangan' : 'Tanggal Penemuan';
    $locationLabel = $item->type === 'lost' ? 'Lokasi Terakhir' : 'Lokasi Penemuan';
@endphp

<label for="{{ $toggleId }}"
    class="detail-overlay invisible fixed inset-0 z-50 cursor-pointer bg-black/50 opacity-0 backdrop-blur-[2px] [transition:opacity_200ms_ease-out,visibility_10ms]"
    aria-hidden="true"></label>

<div class="detail-panel invisible fixed inset-0 z-50 flex items-end justify-center opacity-0 [transition:opacity_200ms_ease-out,visibility_200ms]"
    id="detail-{{ $item->item_id }}">

    <div
        class="relative z-10 flex h-dvh w-full flex-col overflow-hidden bg-surface shadow-2xl sm:h-[calc(100dvh-3rem)] sm:rounded-t-control">

        <div class="flex-1 overflow-y-auto overscroll-contain">

            <div class="sm:mx-auto sm:max-w-3xl sm:px-10 sm:pt-10">

                <div class="flex justify-end px-5 pt-4 sm:hidden">
                    <label for="{{ $toggleId }}"
                        class="flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-surface/80 backdrop-blur-sm transition hover:bg-surface"
                        aria-label="Tutup detail">
                        <x-icons.close class="h-5 w-5 text-ink" />
                    </label>
                </div>

                <div class="mt-1 flex items-center justify-between gap-3 px-5 sm:mt-0">
                    <p class="min-w-0 truncate text-body-sm text-muted">
                        {{ $item->category->name }}
                    </p>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <span @class([
                            'inline-flex items-center rounded-control border-[0.5px] px-2.5 py-0.5 text-caption font-semibold',
                            $typeBadgeClass,
                        ])>
                            {{ $typeLabel }}
                        </span>
                        <span @class([
                            'inline-flex items-center rounded-control border-[0.5px] px-2.5 py-0.5 text-caption font-semibold',
                            $statusBadgeClass,
                        ])>
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>

                <h2 class="mt-1 px-5 text-title font-display sm:text-headline">
                    {{ $item->title }}
                </h2>

                @if ($images->isNotEmpty())
                    <div class="mt-4 px-5 sm:mt-6" data-gallery>
                        <div class="aspect-square overflow-hidden rounded-control bg-black/5">
                            <img src="{{ asset('storage/' . $firstImage->path) }}" alt="{{ $item->title }}"
                                class="h-full w-full object-cover" data-main-image />
                        </div>

                        @if ($images->count() > 1)
                            <div class="mt-2 flex gap-2 overflow-x-auto pb-1 sm:mt-3">
                                @foreach ($images as $index => $image)
                                    <button type="button" aria-label="Lihat foto {{ $index + 1 }}" @class([
                                        'size-16 shrink-0 overflow-hidden rounded-control bg-black/5 ring-2 ring-inset transition hover:opacity-100 sm:size-20',
                                        'ring-primary' => $index === 0,
                                        'opacity-60 ring-transparent' => $index !== 0,
                                    ])
                                        data-thumb-index="{{ $index }}" data-thumb-src="{{ asset('storage/' . $image->path) }}">
                                        <img src="{{ asset('storage/' . $image->path) }}" alt=""
                                            class="h-full w-full object-cover" />
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <p class="mt-4 px-5 text-body-sm leading-relaxed text-muted sm:mt-6 sm:text-body">
                    {{ $item->description }}
                </p>

                <div class="mt-5 flex items-center gap-3 px-5">
                    <x-icons.map-pin class="h-5 w-5 shrink-0 text-ink" />
                    <div>
                        <p class="text-caption text-muted">{{ $locationLabel }}</p>
                        <p class="text-subtitle font-display">
                            {{ $item->location->name }}
                        </p>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-3 px-5 pb-6 sm:pb-10">
                    <x-icons.calendar class="h-5 w-5 shrink-0 text-ink" />
                    <div>
                        <p class="text-caption text-muted">{{ $dateLabel }}</p>
                        <p class="text-subtitle font-display">
                            {{ $item->date_event->locale('id')->translatedFormat('l, j M Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @if ($showAction)
            <div class="border-t border-line/30 pb-5 pt-4">
                <div class="px-5 sm:mx-auto sm:max-w-3xl sm:px-10">
                    <div class="sm:flex sm:items-center sm:justify-between sm:gap-6 sm:px-5">
                        <p class="mb-2 text-body-sm text-muted sm:mb-0">
                            {{ $ctaHelper }}
                        </p>
                        @if ($isClosed)
                            <span
                                class="block rounded-control bg-black/10 px-5 py-3 text-center text-label text-muted cursor-not-allowed sm:px-10">
                                {{ $ctaLabel }}
                            </span>
                        @elseif ($isResolved)
                            <a href="{{ $ctaUrl }}"
                                class="block rounded-control border-[0.5px] border-primary bg-transparent px-5 py-3 text-center text-label text-primary transition hover:bg-primary/10 sm:px-10">
                                {{ $ctaLabel }}
                            </a>
                        @else
                            <a href="{{ $ctaUrl }}"
                                class="block rounded-control bg-primary px-5 py-3 text-center text-label text-white transition hover:bg-primary/90 sm:px-10">
                                {{ $ctaLabel }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <label for="{{ $toggleId }}" class="absolute inset-0 z-0 cursor-pointer" aria-hidden="true"></label>

    <label for="{{ $toggleId }}"
        class="absolute right-4 top-2 z-20 hidden h-8 w-8 cursor-pointer items-center justify-center rounded-full text-surface transition hover:bg-surface/20 sm:flex"
        aria-label="Tutup detail">
        <x-icons.close class="h-6 w-6" />
    </label>
</div>