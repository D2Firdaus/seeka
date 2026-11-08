@props(['item'])

<article class="flex flex-col gap-4 rounded-control border-[0.5px] border-line bg-white p-4">
    <div class="flex gap-4">
        <div class="aspect-square shrink-0 self-stretch rounded-control bg-black/5"></div>

        <div class="flex min-w-0 flex-1 flex-col justify-center gap-1.5">
            <div class="flex items-start justify-between gap-3">
                <p class="min-w-0 truncate text-body text-muted lg:text-caption">{{ $item->category->name }}</p>
                <p class="shrink-0 text-body text-faint lg:text-caption">
                    {{ $item->type === 'lost' ? 'Kehilangan' : 'Penemuan' }}
                </p>
            </div>

            <h2 class="line-clamp-2 text-title font-bold lg:line-clamp-1">{{ $item->title }}</h2>

            <p class="flex items-center gap-1.5 text-body text-ink-soft lg:text-caption">
                <x-icons.map-pin class="h-4 w-4 shrink-0" />
                <span class="min-w-0 truncate">{{ $item->location->name }}</span>
            </p>

            <p class="text-body text-ink-soft lg:text-caption">
                {{ $item->date_event->locale('id')->translatedFormat('j F Y') }}
            </p>
        </div>
    </div>

    <p class="line-clamp-2 flex-1 text-body text-muted lg:text-caption">{{ $item->description }}</p>

    <a href="{{ auth()->check() ? '#' : route('login') }}"
        class="rounded-control bg-primary px-5 py-3 text-center text-body font-semibold text-white transition hover:bg-primary/90">
        Ajukan Klaim
    </a>
</article>