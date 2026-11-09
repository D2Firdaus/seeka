@extends('layouts.app')

@section('title', 'Ajukan Penemuan — ' . config('app.name', 'SEEKA'))

@section('content')
    <main class="mx-auto w-full max-w-7xl flex-1 px-5 pb-16 pt-8 sm:px-8">
        <header class="mb-8">
            <h1 class="text-display font-display">
                Ajukan Penemuan
            </h1>

            <p class="mt-3 text-title font-display font-normal text-ink-soft">
                Laporkan barang temuan yang cocok dengan laporan kehilangan ini
            </p>
        </header>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,42rem)_1fr] lg:items-start lg:gap-12">

            {{-- FORM: mobile di bawah kartu, desktop di kiri --}}
            <div class="w-full max-w-2xl order-2 lg:order-1">
                @if ($errors->any())
                    <x-alert type="error" class="mb-6">{{ $errors->first() }}</x-alert>
                @endif

                <form method="POST" action="{{ route('penemuan.store', $item) }}" enctype="multipart/form-data">
                    @csrf

                    <div>
                        <label for="existing_found_item_id" class="mb-2 block text-label text-ink">
                            Hubungkan dengan Laporan Penemuan Anda
                            <span class="text-caption font-normal text-muted">(Opsional)</span>
                        </label>
                        <div class="relative">
                            <select id="existing_found_item_id" name="existing_found_item_id"
                                class="w-full appearance-none rounded-control border-[0.5px] border-line bg-white py-3 pl-3.5 pr-10 text-body text-ink outline-none transition has-[option[value='']:checked]:text-black/40 focus:border-primary">
                                <option value="" class="text-ink">Pilih laporan penemuan Anda (jika ada)</option>
                                @foreach ($userFoundItems as $foundItem)
                                    <option value="{{ $foundItem->item_id }}" class="text-ink"
                                        @selected(old('existing_found_item_id') == $foundItem->item_id)>
                                        {{ $foundItem->title }}
                                        ({{ $foundItem->date_event->locale('id')->translatedFormat('j M Y') }})
                                    </option>
                                @endforeach
                            </select>
                            <x-icons.chevron-right
                                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-ink" />
                        </div>
                        <p class="mt-1.5 text-caption text-muted">
                            Memilih laporan penemuan yang sudah Anda buat akan mempercepat verifikasi oleh petugas.
                        </p>
                    </div>

                    <div class="mt-6">
                        <label for="finding_description" class="mb-2 block text-label text-ink">
                            Deskripsi Penemuan & Kondisi Barang<span class="text-primary">*</span>
                        </label>
                        <textarea id="finding_description" name="finding_description" rows="5" required
                            placeholder="Jelaskan kondisi barang saat ditemukan, lokasi detail penemuan, dan kecocokan ciri dengan laporan kehilangan ini."
                            class="w-full resize-none rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-black/40 focus:border-primary">{{ old('finding_description') }}</textarea>
                        <p class="mt-1.5 text-caption text-muted">
                            Informasi ini akan dicocokkan dengan data pemilik untuk memastikan barang sampai ke tangan yang tepat.
                        </p>
                    </div>

                    <div class="mt-6">
                        <label for="storage_location" class="mb-2 block text-label text-ink">
                            Lokasi Penyimpanan Saat Ini
                            <span class="text-caption font-normal text-muted">(Opsional)</span>
                        </label>
                        <input id="storage_location" name="storage_location" type="text"
                            value="{{ old('storage_location') }}"
                            placeholder="Contoh: Pos Satpam Gedung Fasilkom, Ruang TU, dll."
                            class="w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-black/40 focus:border-primary" />
                        <p class="mt-1.5 text-caption text-muted">
                            Di mana barang berada sekarang agar mudah dikoordinasikan saat serah terima.
                        </p>
                    </div>

                    <div class="mt-6">
                        <p class="mb-2 text-label text-ink">
                            Foto Barang Temuan
                            <span class="text-caption font-normal text-muted">(Opsional)</span>
                        </p>
                        <div
                            class="flex flex-wrap items-center gap-4 rounded-control border-[0.5px] border-line bg-white px-4 py-4">
                            <x-icons.add-photo class="h-5 w-5 text-primary" />
                            <p class="flex-1 text-body-sm text-ink-soft">
                                Foto barang yang Anda temukan (JPG/PNG maks. 5 MB)
                            </p>
                            <label id="proof-photo-picker-label" for="proof_image"
                                class="cursor-pointer rounded-control border-[0.5px] border-line px-4 py-2 text-label text-ink transition hover:border-black/40">
                                Pilih Foto
                            </label>
                        </div>
                        <input id="proof_image" name="proof_image" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                            class="sr-only" />
                        <p id="proof-photo-error" class="mt-2 hidden text-label text-primary"></p>
                        <div id="proof-photo-preview" class="mt-3 hidden grid grid-cols-2 gap-3 min-[380px]:grid-cols-3">
                            <div class="relative overflow-hidden rounded-control border-[0.5px] border-line bg-white">
                                <img id="proof-preview-img" alt="Preview bukti" class="aspect-square w-full bg-black/5 object-cover" />
                                <div class="flex items-center justify-between gap-2 px-2 py-1.5">
                                    <span id="proof-preview-name" class="min-w-0 truncate text-caption text-ink-soft"></span>
                                    <button type="button" id="proof-photo-remove"
                                        class="shrink-0 rounded-control p-1 text-ink-soft transition hover:bg-black/5 hover:text-primary"
                                        aria-label="Hapus foto">
                                        <x-icons.close class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <label class="mt-6 flex items-start gap-2.5 text-body text-ink">
                        <input type="checkbox" name="declaration" value="1" required @checked(old('declaration'))
                            class="mt-1 h-4 w-4 rounded-control border-line accent-primary" />
                        <span>
                            Saya menyatakan bahwa informasi penemuan ini benar dan barang berada di tempat/kondisi yang saya sebutkan.<span class="text-primary">*</span>
                        </span>
                    </label>

                    {{-- Alur Penemuan: hanya tampil di mobile, tepat sebelum tombol --}}
                    <x-alur-penemuan class="mt-8 lg:hidden" />

                    <div class="mt-8 grid grid-cols-1 min-[380px]:grid-cols-2 gap-4">
                        <a href="{{ route('home') }}"
                            class="flex items-center justify-center rounded-control border-[0.5px] border-line px-5 py-4 text-center text-label text-ink transition hover:border-black/40">
                            Batal
                        </a>

                        <button type="submit"
                            class="rounded-control bg-primary px-5 py-4 text-body font-semibold text-white transition hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                            Kirim Penemuan
                        </button>
                    </div>
                </form>
            </div>

            {{-- ASIDE: mobile di atas form, desktop di kanan --}}
            <aside class="flex w-full flex-col gap-6 order-1 lg:order-2 lg:sticky lg:top-6">

                <div>
                    <p class="mb-2 text-label text-ink">Barang yang Dilaporkan Hilang</p>

                    <div class="grid grid-cols-1">
                        <x-item-card :item="$item" :show-action="false" />
                    </div>
                </div>

                {{-- Alur Penemuan: hanya tampil di desktop --}}
                <x-alur-penemuan class="hidden lg:block" />
            </aside>
        </div>
    </main>
@endsection
