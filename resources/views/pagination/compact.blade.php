@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-wrap items-center justify-center gap-1">
        @unless ($paginator->onFirstPage())
            <a href="{{ $paginator->url(1) }}" rel="first" aria-label="Halaman pertama"
                class="inline-flex items-center justify-center rounded-control px-2 py-2 sm:px-3 text-ink transition hover:text-primary">
                <x-icons.chevron-right-double class="h-4 w-4 rotate-180" />
            </a>
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya"
                class="inline-flex items-center justify-center rounded-control px-2 py-2 sm:px-3 text-ink transition hover:text-primary">
                <x-icons.chevron-right class="h-4 w-4 rotate-180" />
            </a>
        @endunless

        @php
            $from = max(1, $paginator->currentPage() - 2);
            $to = min($paginator->lastPage(), $from + 4);
            $from = max(1, $to - 4);
        @endphp

        @for ($page = $from; $page <= $to; $page++)
            @if ($page === $paginator->currentPage())
                <span aria-current="page"
                    class="inline-flex items-center justify-center rounded-control bg-primary px-3 py-2 text-body font-semibold text-white">
                    {{ $page }}
                </span>
            @else
                <a href="{{ $paginator->url($page) }}" aria-label="Halaman {{ $page }}"
                    class="inline-flex items-center justify-center rounded-control px-2 py-2 sm:px-3 text-body font-semibold text-ink transition hover:text-primary">
                    {{ $page }}
                </a>
            @endif
        @endfor

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya"
                class="inline-flex items-center justify-center rounded-control px-2 py-2 sm:px-3 text-ink transition hover:text-primary">
                <x-icons.chevron-right class="h-4 w-4" />
            </a>
            <a href="{{ $paginator->url($paginator->lastPage()) }}" rel="last" aria-label="Halaman terakhir"
                class="inline-flex items-center justify-center rounded-control px-2 py-2 sm:px-3 text-ink transition hover:text-primary">
                <x-icons.chevron-right-double class="h-4 w-4" />
            </a>
        @endif
    </nav>
@endif
