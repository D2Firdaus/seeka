@props(['item', 'toggleId'])

@php
    $images = $item->images;
    $firstImage = $images->first();
    $typeLabel = $item->type === 'lost' ? 'Kehilangan' : 'Penemuan';
    $typeBadgeClass = $item->type === 'lost'
        ? 'bg-red-50 text-red-700 ring-red-600/20'
        : 'bg-green-50 text-green-700 ring-green-600/20';
    $ctaLabel = $item->type === 'lost' ? 'Lapor Penemuan' : 'Ajukan Klaim';
    $dateLabel = $item->type === 'lost' ? 'Tanggal Kehilangan' : 'Tanggal Penemuan';
    $locationLabel = $item->type === 'lost' ? 'Lokasi Terakhir' : 'Lokasi Penemuan';
@endphp

{{-- Overlay --}}
<label for="{{ $toggleId }}"
    class="detail-overlay invisible fixed inset-0 z-50 cursor-pointer bg-black/50 opacity-0 backdrop-blur-[2px] [transition:opacity_200ms_ease-out,visibility_10ms]"
    aria-hidden="true"></label>

{{-- Modal panel --}}
<div
    class="detail-panel invisible fixed inset-0 z-50 flex items-end justify-center opacity-0 [transition:opacity_200ms_ease-out,visibility_200ms]"
    id="detail-{{ $item->item_id }}">

    <div
        class="relative flex h-dvh w-full flex-col overflow-hidden bg-white shadow-2xl sm:h-[90dvh] sm:max-w-md sm:rounded-t-2xl">

        {{-- Close button --}}
        <label for="{{ $toggleId }}"
            class="absolute right-3 top-3 z-10 flex h-8 w-8 cursor-pointer items-center justify-center rounded-full bg-white/80 backdrop-blur-sm transition hover:bg-white"
            aria-label="Tutup detail">
            <x-icons.close class="h-5 w-5 text-ink" />
        </label>

        {{-- Scrollable content --}}
        <div class="flex-1 overflow-y-auto overscroll-contain">

            {{-- Header: category + badge --}}
            <div class="flex items-center justify-between px-5 pt-5">
                <p class="text-body-sm text-muted">
                    {{ $item->category->name }}
                </p>
                <span @class([
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-caption font-semibold ring-1 ring-inset',
                    $typeBadgeClass,
                ])>
                    {{ $typeLabel }}
                </span>
            </div>

            {{-- Title --}}
            <h2 class="mt-1 px-5 text-title font-display">
                {{ $item->title }}
            </h2>

            {{-- Image gallery --}}
            @if ($images->isNotEmpty())
                <div class="mt-4 px-5" data-gallery>
                    {{-- Main image --}}
                    <div class="aspect-4/3 overflow-hidden rounded-control bg-black/5">
                        <img src="{{ asset('storage/' . $firstImage->path) }}" alt="{{ $item->title }}"
                            class="h-full w-full object-cover" data-main-image />
                    </div>

                    {{-- Thumbnails --}}
                    @if ($images->count() > 1)
                        <div class="mt-2 flex gap-2">
                            @foreach ($images as $index => $image)
                                <button type="button"
                                    class="aspect-square w-16 shrink-0 overflow-hidden rounded-control ring-2 transition {{ $index === 0 ? 'ring-primary' : 'ring-transparent hover:ring-primary/40' }}"
                                    data-thumb-index="{{ $index }}"
                                    data-thumb-src="{{ asset('storage/' . $image->path) }}">
                                    <img src="{{ asset('storage/' . $image->path) }}" alt=""
                                        class="h-full w-full object-cover" />
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- Description --}}
            <p class="mt-4 px-5 text-body-sm leading-relaxed text-muted">
                {{ $item->description }}
            </p>

            {{-- Location --}}
            <div class="mt-5 flex items-start gap-3 px-5">
                <x-icons.map-pin class="mt-0.5 h-5 w-5 text-ink" />
                <div>
                    <p class="text-caption text-muted">{{ $locationLabel }}</p>
                    <p class="text-body font-display font-semibold">
                        {{ $item->location->name }}
                    </p>
                </div>
            </div>

            {{-- Date --}}
            <div class="mt-3 flex items-start gap-3 px-5 pb-6">
                <x-icons.calendar class="mt-0.5 h-5 w-5 text-ink" />
                <div>
                    <p class="text-caption text-muted">{{ $dateLabel }}</p>
                    <p class="text-body font-display font-semibold">
                        {{ $item->date_event->locale('id')->translatedFormat('l, j M Y') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Sticky CTA --}}
        <div class="border-t border-line/30 px-5 pb-5 pt-4">
            <p class="mb-2 text-body-sm text-muted">
                {{ $item->type === 'lost' ? 'Menemukan Barang Ini?' : 'Ini Barang Anda?' }}
            </p>
            <a href="{{ auth()->check() ? '#' : route('login') }}"
                class="block rounded-control bg-primary px-5 py-3 text-center text-label text-white transition hover:bg-primary/90">
                {{ $ctaLabel }}
            </a>
        </div>
    </div>
</div>
