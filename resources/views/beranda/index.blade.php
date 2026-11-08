@extends('layouts.app')

@section('title', 'Beranda ' . config('app.name', 'SEEKA'))

@section('content')
    <main class="mx-auto w-full max-w-6xl flex-1 px-5 pb-16 pt-8 sm:px-8">
        <h1 class="text-display font-display">SEEKA</h1>

        <p class="mt-3 max-w-md text-title font-display font-normal text-ink-soft">
            Bantu Barang Kembali Kepada Pemiliknya
        </p>

        <a href="#"
            class="mt-5 inline-flex items-center gap-2 rounded-control border-[0.5px] border-line bg-white px-4 py-2 text-label transition hover:border-black/40">
            <x-icons.book class="h-5 w-5" />
            Panduan
        </a>

        <form method="GET" action="{{ route('home') }}" class="mt-6">
            <div class="flex flex-wrap items-stretch gap-3">
                <div class="flex h-11 min-w-[12rem] flex-1 items-stretch lg:h-[3.125rem]">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Judul Barang"
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

            <x-filter-modal :categories="$categories" :locations="$locations" />

            <textarea name="description" rows="3" placeholder="Deskripsi Barang"
                class="mt-3 w-full resize-none rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body outline-none transition hover:border-black/40 focus:border-primary">{{ request('description') }}</textarea>
        </form>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <p class="hidden min-[512px]:block text-body-sm text-muted">
                Menampilkan {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} data
            </p>

            <p class="min-[512px]:hidden text-body-sm text-muted">
                {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }}<span
                    class="hidden min-[360px]:inline"> dari {{ $items->total() }}</span> data
            </p>

            <form method="GET" action="{{ route('home') }}" class="flex items-center gap-2">
                @foreach (request()->query() as $key => $value)
                    @if ($key === 'page' || $key === 'per_page' || blank($value))
                        @continue
                    @endif
                    @foreach (Arr::wrap($value) as $entry)
                        <input type="hidden" name="{{ is_array($value) ? $key.'[]' : $key }}" value="{{ $entry }}">
                    @endforeach
                @endforeach

                <label for="per_page" class="text-label text-ink-soft"><span
                        class="hidden min-[512px]:inline">Data per halaman</span><span
                        class="min-[512px]:hidden">Menampilkan</span></label>

                <div class="relative">
                    <select id="per_page" name="per_page" onchange="this.form.submit()"
                        class="appearance-none rounded-control border-[0.5px] border-line bg-white py-2 pl-3.5 pr-9 text-body outline-none transition hover:border-black/40 focus:border-primary">
                        @foreach ([9, 18, 36] as $size)
                            <option value="{{ $size }}" @selected(request('per_page', 9) == $size)>{{ $size }}</option>
                        @endforeach
                    </select>

                    <x-icons.chevron-right
                        class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-muted" />
                </div>
            </form>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($items as $item)
                <x-item-card :item="$item" />
            @empty
                <p class="text-body text-muted sm:col-span-2 lg:col-span-3">
                    Belum ada laporan yang cocok dengan pencarianmu.
                </p>
            @endforelse
        </div>

        <div class="mt-10">
            {{ $items->links('pagination.compact') }}
        </div>
    </main>
@endsection