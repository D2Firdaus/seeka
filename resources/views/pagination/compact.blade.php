@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();

        // Window halaman yang ditampilkan
        if ($current <= 4) {
            $from = 1;
            $to = min($last, 6);
        } elseif ($current >= $last - 3) {
            $from = max(1, $last - 5);
            $to = $last;
        } else {
            $from = $current - 2;
            $to = $current + 2;
        }

        // Ukuran sama untuk semua item, hanya ini yang diatur
        $base = 'inline-flex h-9 min-w-9 items-center justify-center rounded-control px-2 sm:px-3 leading-none';
        $link = $base . ' text-subtitle text-ink transition hover:text-primary';
        $icon = $base . ' text-ink transition hover:text-primary';
        $active = $base . ' bg-primary text-subtitle text-white';
        $dots = $base . ' text-subtitle text-ink select-none';
    @endphp

    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-wrap items-center justify-center gap-1">
        {{-- Pertama & sebelumnya --}}
        @unless ($paginator->onFirstPage())
            <a href="{{ $paginator->url(1) }}" rel="first" aria-label="Halaman pertama" class="{{ $icon }}">
                <x-icons.chevron-right-double class="h-4 w-4 rotate-180" />
            </a>
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" class="{{ $icon }}">
                <x-icons.chevron-right class="h-4 w-4 rotate-180" />
            </a>
        @endunless

        {{-- Halaman 1 + titik-titik di awal --}}
        @if ($from > 1)
            <a href="{{ $paginator->url(1) }}" aria-label="Halaman 1" class="{{ $link }}">1</a>
            @if ($from > 2)
                <span class="{{ $dots }}" aria-hidden="true">…</span>
            @endif
        @endif

        {{-- Window halaman --}}
        @for ($page = $from; $page <= $to; $page++)
            @if ($page === $current)
                <span aria-current="page" class="{{ $active }}">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}" aria-label="Halaman {{ $page }}" class="{{ $link }}">
                    {{ $page }}
                </a>
            @endif
        @endfor

        {{-- Titik-titik + halaman terakhir --}}
        @if ($to < $last)
            @if ($to < $last - 1)
                <span class="{{ $dots }}" aria-hidden="true">…</span>
            @endif
            <a href="{{ $paginator->url($last) }}" aria-label="Halaman {{ $last }}" class="{{ $link }}">
                {{ $last }}
            </a>
        @endif

        {{-- Berikutnya & terakhir --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" class="{{ $icon }}">
                <x-icons.chevron-right class="h-4 w-4" />
            </a>
            <a href="{{ $paginator->url($last) }}" rel="last" aria-label="Halaman terakhir" class="{{ $icon }}">
                <x-icons.chevron-right-double class="h-4 w-4" />
            </a>
        @endif
    </nav>
@endif