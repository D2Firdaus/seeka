@props(['item'])

<article class="item-card flex flex-col h-full gap-4 rounded-control border-[0.5px] border-line bg-white p-4">
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

            {{-- Balanced title --}}
            @php
                $words = explode(' ', trim($item->title));
                $totalWords = count($words);

                $firstLine = '';
                $secondLine = '';

                if ($totalWords > 1) {
                    // Put all but the last word on the first line, last word on the second line
                    $splitIndex = $totalWords - 1;
                    $firstLine = implode(' ', array_slice($words, 0, $splitIndex));
                    $secondLine = implode(' ', array_slice($words, $splitIndex));
                } else {
                    // Single‑word title: keep it on one line
                    $firstLine = $words[0];
                    $secondLine = '';
                }
            @endphp

            <h2 class="line-clamp-2 text-subtitle font-display text-balance item-card-title">
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

    <p class="line-clamp-2 flex-1 text-body-sm text-muted">
        {{ $item->description }}
    </p>

    <a href="{{ auth()->check() ? '#' : route('login') }}"
        class="rounded-control bg-primary px-5 py-3 text-center text-label text-white transition hover:bg-primary/90">
        Ajukan Klaim
    </a>
</article>