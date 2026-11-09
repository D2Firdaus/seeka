@props(['item'])

@php
    $toggleId = 'detail-toggle-' . $item->item_id;
@endphp

<div class="h-full">
    <input type="checkbox" id="{{ $toggleId }}" class="detail-toggle peer sr-only" />

    <article
        class="item-card flex h-full cursor-pointer flex-col gap-4 rounded-control border-[0.5px] border-line bg-white p-4">

        <label for="{{ $toggleId }}" class="contents cursor-pointer">
            <div class="flex gap-4">
                <div class="relative aspect-square shrink-0 self-stretch overflow-hidden rounded-control bg-black/5">
                    @if ($item->images->isNotEmpty())
                        <img src="{{ asset('storage/' . $item->images->first()->path) }}" alt="{{ $item->title }}"
                            class="absolute inset-0 h-full w-full object-cover" />
                    @endif
                </div>

                <div class="flex min-w-0 flex-1 flex-col justify-between gap-1.5">
                    <div class="flex items-start justify-between gap-3">
                        <p class="min-w-0 truncate text-body-sm text-muted">
                            {{ $item->category->name }}
                        </p>

                        <p class="shrink-0 text-body-sm text-faint">
                            {{ $item->type === 'lost' ? 'Kehilangan' : 'Penemuan' }}
                        </p>
                    </div>

                    @php
                        $words = explode(' ', trim($item->title));
                        $totalWords = count($words);

                        $firstLine = '';
                        $secondLine = '';

                        if ($totalWords > 1) {
                            $splitIndex = $totalWords - 1;
                            $firstLine = implode(' ', array_slice($words, 0, $splitIndex));
                            $secondLine = implode(' ', array_slice($words, $splitIndex));
                        } else {
                            $firstLine = $words[0];
                            $secondLine = '';
                        }
                    @endphp

                    <h2 class="item-card-title line-clamp-2 text-subtitle font-display text-balance">
                        @if ($totalWords > 1)
                            {{ $firstLine }}<br>{!! $secondLine !!}
                        @else
                            {{ $firstLine }}
                        @endif
                    </h2>

                    <div class="mt-auto">
                        <p class="flex items-center gap-1.5 text-body-sm text-ink-soft">
                            <x-icons.map-pin class="h-4 w-4 shrink-0" />
                            <span class="min-w-0 truncate">{{ $item->location->name }}</span>
                        </p>

                        <p class="flex items-center gap-1.5 text-body-sm text-ink-soft">
                            <x-icons.calendar class="h-4 w-4 shrink-0" />
                            {{ $item->date_event->locale('id')->translatedFormat('j F Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </label>

        <a href="{{ auth()->check() ? '#' : route('login') }}"
            class="mt-auto rounded-control bg-primary px-5 py-3 text-center text-label text-white transition hover:bg-primary/90">
            Ajukan Klaim
        </a>
    </article>

    <x-item-detail-modal :item="$item" :toggleId="$toggleId" />
</div>