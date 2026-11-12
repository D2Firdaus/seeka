@extends('layouts.app')

@section('title', 'Laporan Saya — ' . config('app.name', 'SEEKA'))

@section('content')
    <main class="mx-auto w-full max-w-7xl flex-1 px-5 pb-16 pt-6 sm:px-8">
        <h1 class="text-display font-display">Laporan Saya</h1>

        {{-- Form Pencarian & Filter --}}
        <form method="GET" action="{{ route('laporan.saya') }}" class="mt-6 mb-5">
            @if (request()->filled('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            @if (request()->filled('status') && ! request()->routeIs('laporan.saya'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <div class="flex flex-wrap items-stretch gap-3">
                <div class="flex h-11 min-w-[12rem] flex-1 items-stretch lg:h-[3.125rem]">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul laporan..."
                        class="w-full min-w-0 rounded-l-control border-[0.5px] border-r-0 border-line bg-white px-3.5 py-3 text-body outline-none transition hover:border-black/40 focus:border-primary" />

                    <button type="submit"
                        class="flex shrink-0 items-center justify-center gap-2 rounded-r-control bg-primary px-5 py-3 text-label text-white transition hover:bg-primary/90">
                        <x-icons.search class="h-5 w-5" />
                        <span class="hidden sm:inline">Cari</span>
                    </button>
                </div>

                <label for="filter-modal-toggle"
                    class="flex h-11 cursor-pointer items-center gap-2 rounded-control bg-primary px-4 text-label text-white transition hover:bg-primary/90 lg:h-[3.125rem]">
                    Filter
                    <x-icons.filter class="h-5 w-5" />
                </label>
            </div>

            <x-filter-modal :categories="$categories" :locations="$locations" :showType="false" :includeClosed="true" />
        </form>

        {{-- Status Filter Aktif & Jumlah Data --}}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 text-body-sm text-muted">
            <p>
                Menampilkan {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} laporan
            </p>

            @if (request()->hasAny(['q', 'category', 'location', 'date_from', 'date_to', 'status']))
                <a href="{{ route('laporan.saya', array_intersect_key(request()->query(), ['type' => 1])) }}"
                    class="text-caption font-semibold text-primary transition hover:underline">
                    Reset Filter
                </a>
            @endif
        </div>

        {{-- List Laporan (Gaya Card List dengan Aksi Langsung) --}}
        <ul class="divide-y divide-black/10 rounded-control border-[0.5px] border-line bg-white">
            @forelse ($items as $item)
                <li class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:gap-4 transition hover:bg-black/[0.015]">
                    {{-- Info Barang (Foto, Judul, Kategori & Tanggal) --}}
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="size-12 shrink-0 overflow-hidden rounded-control bg-black/5">
                            @if ($item->images->isNotEmpty())
                                <img src="{{ asset('storage/' . $item->images->first()->path) }}"
                                    alt="{{ $item->title }}" class="h-full w-full object-cover" />
                            @else
                                <div class="flex h-full w-full items-center justify-center text-muted">
                                    <x-icons.book class="h-5 w-5 opacity-40" />
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-body font-semibold text-ink">{{ $item->title }}</p>
                            <p class="truncate text-body-sm text-muted">
                                {{ $item->category->name }} ·
                                {{ $item->date_event->locale('id')->translatedFormat('j F Y') }}
                            </p>
                        </div>
                    </div>

                    {{-- Badges & Tombol Aksi --}}
                    <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 sm:justify-end">
                        {{-- Badges Jenis & Status --}}
                        <div class="flex items-center gap-1.5">
                            <span @class([
                                'inline-flex items-center rounded-control border-[0.5px] px-2.5 py-0.5 text-caption font-semibold',
                                'bg-lost border-lost text-surface' => $item->type === 'lost',
                                'bg-found border-found text-surface' => $item->type === 'found',
                            ])>
                                {{ $item->type === 'lost' ? 'Kehilangan' : 'Penemuan' }}
                            </span>

                            <span @class([
                                'inline-flex items-center rounded-control border-[0.5px] px-2.5 py-0.5 text-caption font-semibold',
                                'bg-open border-open text-surface' => $item->status === 'open',
                                'bg-claimed border-claimed text-surface' => $item->status === 'claimed',
                                'bg-returned border-returned text-surface' => $item->status === 'returned',
                                'bg-closed border-closed text-surface' => $item->status === 'closed',
                            ])>
                                {{ match ($item->status) {
                                    'claimed' => 'Diklaim',
                                    'returned' => 'Dikembalikan',
                                    'closed' => 'Ditutup',
                                    default => 'Terbuka',
                                } }}
                            </span>
                        </div>

                        {{-- Aksi Langsung: Edit, Tutup & Hapus --}}
                        @if ($item->status === 'open')
                            <div class="flex items-center gap-1 pl-1 sm:border-l sm:border-black/10 sm:pl-3">
                                <a href="{{ route('laporan.edit', $item) }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-control text-ink-soft transition hover:bg-black/5 hover:text-primary"
                                    title="Edit Laporan"
                                    aria-label="Edit Laporan">
                                    <x-icons.edit class="h-5 w-5" />
                                </a>

                                <form method="POST" action="{{ route('laporan.close', $item) }}"
                                    onsubmit="return confirm('Tutup laporan ini? Laporan tidak akan muncul di pencarian publik lagi.')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="flex h-8 w-8 items-center justify-center rounded-control text-ink-soft transition hover:bg-black/5 hover:text-primary"
                                        title="Tutup Laporan"
                                        aria-label="Tutup Laporan">
                                        <x-icons.circle-disallowed class="h-5 w-5" />
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('laporan.destroy', $item) }}"
                                    onsubmit="return confirm('Hapus laporan ini? Tindakan ini tidak dapat dibatalkan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="flex h-8 w-8 items-center justify-center rounded-control text-ink-soft transition hover:bg-primary/5 hover:text-primary"
                                        title="Hapus Laporan"
                                        aria-label="Hapus Laporan">
                                        <x-icons.trash class="h-5 w-5" />
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </li>
            @empty
                <li class="p-8 text-center text-body text-muted">
                    @if (request()->hasAny(['q', 'type', 'status', 'category', 'location', 'date_from', 'date_to']))
                        Belum ada laporan yang cocok dengan filter yang dipilih.
                    @else
                        Anda belum membuat laporan apa pun.
                    @endif
                </li>
            @endforelse
        </ul>

        {{-- Pagination --}}
        @if ($items->hasPages())
            <div class="mt-6">
                {{ $items->links('pagination.compact') }}
            </div>
        @endif
    </main>
@endsection
