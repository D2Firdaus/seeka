@extends('layouts.app')

@section('title', 'Kelola Kategori — ' . config('app.name', 'SEEKA'))

@section('content')
    <main class="mx-auto w-full max-w-7xl flex-1 px-5 pb-16 pt-6 sm:px-8">
        <h1 class="text-display font-display mb-[1.15em]">Kelola Kategori</h1>

        @if ($errors->any())
            <x-alert type="error" class="mb-6">{{ $errors->first() }}</x-alert>
        @endif

        <section>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-title font-display">Daftar Kategori</h2>

                <a href="{{ route('admin.metadata.kategori.create') }}"
                    class="shrink-0 rounded-control bg-primary px-5 py-2.5 text-label text-white transition hover:bg-primary/90">
                    Tambah Kategori
                </a>
            </div>

            <ul class="mt-4 divide-y divide-black/10 rounded-control border-[0.5px] border-line bg-white">
                @forelse ($categories as $category)
                    <li class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:gap-4 transition hover:bg-black/[0.015]">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-body font-semibold text-ink">{{ $category->name }}</p>
                            <p class="truncate text-body-sm text-muted">
                                {{ $category->slug }} · {{ $category->items_count }} laporan
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <a href="{{ route('admin.metadata.kategori.edit', $category) }}"
                                class="flex h-8 w-8 items-center justify-center rounded-control text-ink-soft transition hover:bg-black/5 hover:text-primary"
                                title="Edit Kategori"
                                aria-label="Edit Kategori">
                                <x-icons.edit class="h-5 w-5" />
                            </a>

                            @if (! $category->items_count)
                                <form method="POST" action="{{ route('admin.metadata.kategori.destroy', $category) }}"
                                    onsubmit="return confirm('Hapus kategori ini? Tindakan ini tidak dapat dibatalkan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="flex h-8 w-8 items-center justify-center rounded-control text-ink-soft transition hover:bg-primary/5 hover:text-primary"
                                        title="Hapus Kategori"
                                        aria-label="Hapus Kategori">
                                        <x-icons.trash class="h-5 w-5" />
                                    </button>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="p-8 text-center text-body text-muted">Belum ada kategori.</li>
                @endforelse
            </ul>
        </section>
    </main>
@endsection
