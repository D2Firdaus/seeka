@extends('layouts.app')

@section('title', 'Edit Laporan ' . config('app.name', 'SEEKA'))

@php
    $isFound = $item->type === 'found';
    $typeLabel = $isFound ? 'Penemuan' : 'Kehilangan';
    $dateLabel = $isFound ? 'Tanggal Penemuan' : 'Tanggal Kehilangan';
    $locationLabel = $isFound ? 'Lokasi Penemuan' : 'Lokasi Kehilangan';
    $removedImageIds = array_map('intval', (array) old('remove_images', []));
@endphp

@section('content')
    <main class="mx-auto w-full max-w-7xl flex-1 px-5 pb-16 pt-8 sm:px-8">
        <header class="mb-8">
            <h1 class="text-display font-display">
                Edit Laporan {{ $typeLabel }}
            </h1>

            <p class="mt-3 text-title font-display font-normal text-ink-soft">
                Perbarui detail laporan "{{ $item->title }}"
            </p>
        </header>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,42rem)_1fr] lg:items-start lg:gap-12">

            {{-- FORM: mobile di bawah kartu, desktop di kiri --}}
            <div class="w-full max-w-2xl order-2 lg:order-1">
                @if ($errors->any())
                    <x-alert type="error" class="mb-6">{{ $errors->first() }}</x-alert>
                @endif

                <form method="POST" action="{{ route('laporan.update', $item) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-4 min-[380px]:grid-cols-2">
                        <div>
                            <label for="title" class="mb-2 block text-label text-ink">Nama Barang<span
                                    class="text-primary">*</span></label>
                            <input id="title" name="title" type="text"
                                value="{{ old('title', $item->title) }}" required autofocus placeholder="Nama Barang"
                                class="w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-black/40 focus:border-primary" />
                        </div>

                        <div>
                            <label for="category_id" class="mb-2 block text-label text-ink">Kategori<span
                                    class="text-primary">*</span></label>
                            <div class="relative">
                                <select id="category_id" name="category_id" required
                                    class="w-full appearance-none rounded-control border-[0.5px] border-line bg-white py-3 pl-3.5 pr-10 text-body text-ink outline-none transition has-[option[value='']:checked]:text-black/40 focus:border-primary">
                                    <option value="" class="text-ink">Pilih Kategori</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->category_id }}" class="text-ink"
                                            @selected(old('category_id', $item->category_id) == $category->category_id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <x-icons.chevron-right
                                    class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-ink" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label for="description" class="mb-2 block text-label text-ink">Deskripsi Barang<span
                                class="text-primary">*</span></label>
                        <textarea id="description" name="description" rows="5" required
                            placeholder="Ceritakan ciri-ciri barang (merek, warna, ukuran, tanda khusus)."
                            class="w-full resize-none rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-black/40 focus:border-primary">{{ old('description', $item->description) }}</textarea>
                    </div>

                    @if ($isFound)
                        <div class="mt-6">
                            <label for="hidden_detail" class="mb-2 block text-label text-ink">Ciri Khusus Barang<span
                                    class="text-primary">*</span></label>
                            <textarea id="hidden_detail" name="hidden_detail" rows="5" required
                                placeholder="Ciri yang hanya diketahui pemilik (nomor seri, stiker, isi tas)."
                                class="w-full resize-none rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-black/40 focus:border-primary">{{ old('hidden_detail', $item->hidden_detail) }}</textarea>
                            <p class="mt-2 text-body-sm text-ink-soft">*Masukkan Informasi Tersembunyi Agar Admin Dapat Memverifikasi Barang Kepada Pemilik.</p>
                        </div>
                    @endif

                    <div class="mt-6 grid grid-cols-1 gap-4 min-[380px]:grid-cols-2">
                        <div>
                            <label for="location_id" class="mb-2 block text-label text-ink">{{ $locationLabel }}<span
                                    class="text-primary">*</span></label>
                            <div class="relative">
                                <select id="location_id" name="location_id" required
                                    class="w-full appearance-none rounded-control border-[0.5px] border-line bg-white py-3 pl-3.5 pr-10 text-body text-ink outline-none transition has-[option[value='']:checked]:text-black/40 focus:border-primary">
                                    <option value="" class="text-ink">Pilih Lokasi</option>
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->location_id }}" class="text-ink"
                                            @selected(old('location_id', $item->location_id) == $location->location_id)>{{ $location->name }}</option>
                                    @endforeach
                                </select>
                                <x-icons.chevron-right
                                    class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-ink" />
                            </div>
                        </div>

                        <div>
                            <label for="date_event" class="mb-2 block text-label text-ink">{{ $dateLabel }}<span
                                    class="text-primary">*</span></label>
                            <input id="date_event" name="date_event" type="date"
                                value="{{ old('date_event', $item->date_event?->toDateString()) }}" required
                                max="{{ now()->toDateString() }}"
                                class="w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition invalid:text-black/40 focus:border-primary" />
                        </div>
                    </div>

                    @if ($isFound)
                        <div class="mt-6">
                            <label for="storage_location" class="mb-2 block text-label text-ink">Penyimpanan Saat Ini</label>
                            <input id="storage_location" name="storage_location" type="text"
                                value="{{ old('storage_location', $item->storage_location) }}"
                                placeholder="Lokasi barang disimpan sekarang (opsional)."
                                class="w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-black/40 focus:border-primary" />
                        </div>
                    @endif

                    <div class="mt-6">
                        <p class="mb-2 text-label text-ink">Foto Barang</p>

                        @if ($item->images->isNotEmpty())
                            <p class="mb-2 text-body-sm text-ink-soft">Foto saat ini — klik X untuk menghapus.</p>
                            <div class="grid grid-cols-2 gap-3 min-[380px]:grid-cols-3">
                                @foreach ($item->images as $image)
                                    @php
                                        $isRemoved = in_array($image->image_id, $removedImageIds, true);
                                    @endphp
                                    <div @class([
                                        'relative overflow-hidden rounded-control border-[0.5px] border-line bg-white',
                                        'hidden' => $isRemoved,
                                    ]) data-existing-photo="{{ $image->image_id }}">
                                        <img src="{{ asset('storage/' . $image->path) }}" alt="Foto {{ $item->title }}"
                                            class="aspect-square w-full bg-black/5 object-cover" />
                                        <div class="flex items-center justify-between gap-2 px-2 py-1.5">
                                            <span class="min-w-0 truncate text-caption text-ink-soft">Foto {{ $loop->iteration }}</span>
                                            <button type="button"
                                                data-existing-photo-remove="{{ $image->image_id }}"
                                                class="shrink-0 rounded-control p-1 text-ink-soft transition hover:bg-black/5 hover:text-primary"
                                                aria-label="Hapus foto">
                                                <x-icons.close class="h-4 w-4" />
                                            </button>
                                        </div>

                                        @if ($isRemoved)
                                            <input type="hidden" name="remove_images[]" value="{{ $image->image_id }}">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div
                            class="mt-4 flex flex-wrap items-center gap-4 rounded-control border-[0.5px] border-line bg-white px-4 py-4">
                            <x-icons.add-photo class="h-5 w-5 text-primary" />
                            <p class="flex-1 text-body-sm text-ink-soft">JPG atau PNG Maks. 5 MB per foto, maksimal 3 foto
                                total<span id="photo-count" class="ml-1 font-semibold text-ink"></span></p>
                            <label id="photo-picker-label" for="photos"
                                class="cursor-pointer rounded-control border-[0.5px] border-line px-4 py-2 text-label text-ink transition hover:border-black/40">
                                Tambah Foto
                            </label>
                        </div>
                        <input id="photos" name="photos[]" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" multiple
                            class="sr-only" />
                        <p id="photo-error" class="mt-2 hidden text-label text-primary"></p>
                        <div id="photo-previews" class="mt-3 hidden grid grid-cols-2 gap-3 min-[380px]:grid-cols-3"></div>
                        <template id="photo-preview-template">
                            <div class="relative overflow-hidden rounded-control border-[0.5px] border-line bg-white">
                                <img alt="" class="aspect-square w-full bg-black/5 object-cover" />
                                <div class="flex items-center justify-between gap-2 px-2 py-1.5">
                                    <span class="min-w-0 truncate text-caption text-ink-soft" data-photo-name></span>
                                    <button type="button" data-photo-remove
                                        class="shrink-0 rounded-control p-1 text-ink-soft transition hover:bg-black/5 hover:text-primary"
                                        aria-label="Hapus foto">
                                        <x-icons.close class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-8 grid grid-cols-1 gap-4 min-[380px]:grid-cols-2">
                        <a href="{{ route('laporan.saya') }}"
                            class="flex items-center justify-center rounded-control border-[0.5px] border-line px-5 py-4 text-center text-label text-ink transition hover:border-black/40">
                            Batal
                        </a>

                        <button type="submit"
                            class="rounded-control bg-primary px-5 py-4 text-body font-semibold text-white transition hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                            Perbarui
                        </button>
                    </div>
                </form>
            </div>

            {{-- ASIDE: mobile di atas form, desktop di kanan --}}
            <aside class="flex w-full flex-col gap-6 order-1 lg:order-2">
                <div>
                    <p class="mb-2 text-label text-ink">Detail Laporan</p>

                    <div class="grid grid-cols-1">
                        <x-item-card :item="$item" :show-action="false" />
                    </div>
                </div>
            </aside>
        </div>
    </main>
@endsection
