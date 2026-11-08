@extends('layouts.app')

@section('title', 'Laporan Kehilangan ' . config('app.name', 'SEEKA'))

@section('content')
    <main class="mx-auto w-full max-w-2xl flex-1 px-5 pb-16 pt-8 sm:px-8">
        <h1 class="text-title">
            Laporan Kehilangan
        </h1>

        <p class="mt-3 text-subtitle text-balance text-ink-soft">
            Kehilangan?, coba ceritakan detailnya agar lebih mudah dikenali
        </p>

        @if ($errors->any())
            <x-alert type="error" class="mt-7">{{ $errors->first() }}</x-alert>
        @endif

        <form method="POST" action="{{ route('lapor.kehilangan') }}" enctype="multipart/form-data" class="mt-8">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="title" class="mb-1.5 block text-body font-semibold text-ink">Nama Barang<span
                            class="text-primary">*</span></label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" required autofocus
                        class="w-full rounded-control border-[0.5px] border-line bg-white px-3 py-2.5 text-body text-ink outline-none transition focus:border-primary" />
                </div>

                <div>
                    <label for="category_id" class="mb-1.5 block text-body font-semibold text-ink">Kategori<span
                            class="text-primary">*</span></label>
                    <div class="relative">
                        <select id="category_id" name="category_id" required
                            class="w-full appearance-none rounded-control border-[0.5px] border-line bg-white py-2.5 pl-3 pr-10 text-body text-ink outline-none transition has-[option[value='']:checked]:text-black/40 focus:border-primary">
                            <option value="" class="text-ink">Pilih Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->category_id }}" class="text-ink"
                                    @selected(old('category_id') == $category->category_id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-icons.chevron-right
                            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-ink" />
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <label for="description" class="mb-1.5 block text-body font-semibold text-ink">Deskripsi Barang<span
                        class="text-primary">*</span></label>
                <textarea id="description" name="description" rows="5" required
                    class="w-full resize-none rounded-control border-[0.5px] border-line bg-white px-3 py-2.5 text-body text-ink outline-none transition focus:border-primary">{{ old('description') }}</textarea>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-4">
                <div>
                    <label for="location_id" class="mb-1.5 block text-body font-semibold text-ink">Lokasi Kehilangan<span
                            class="text-primary">*</span></label>
                    <div class="relative">
                        <select id="location_id" name="location_id" required
                            class="w-full appearance-none rounded-control border-[0.5px] border-line bg-white py-2.5 pl-3 pr-10 text-body text-ink outline-none transition has-[option[value='']:checked]:text-black/40 focus:border-primary">
                            <option value="" class="text-ink">Pilih Lokasi</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->location_id }}" class="text-ink"
                                    @selected(old('location_id') == $location->location_id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                        <x-icons.chevron-right
                            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-ink" />
                    </div>
                </div>

                <div>
                    <label for="date_event" class="mb-1.5 block text-body font-semibold text-ink">Tanggal Kehilangan<span
                            class="text-primary">*</span></label>
                    <input id="date_event" name="date_event" type="date" value="{{ old('date_event') }}" required
                        max="{{ now()->toDateString() }}"
                        class="w-full rounded-control border-[0.5px] border-line bg-white px-3 py-2.5 text-body text-ink outline-none transition focus:border-primary" />
                </div>
            </div>

            <div class="mt-5">
                <p class="mb-1.5 text-body font-semibold text-ink">Foto Barang</p>
                <div class="flex items-center gap-4 rounded-control border-[0.5px] border-line bg-white px-4 py-3">
                    <x-icons.add-photo class="h-10 w-10 text-primary" />
                    <p class="flex-1 text-body text-ink-soft">JPG atau PNG Maks. 5 MB per foto hingga 3 foto</p>
                    <label for="photos"
                        class="cursor-pointer rounded-control border-[0.5px] border-line px-4 py-2 text-body font-semibold text-ink transition hover:border-black/40">
                        Pilih Foto
                    </label>
                </div>
                <input id="photos" name="photos[]" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" multiple
                    class="sr-only" />
            </div>

            <label class="mt-5 flex items-start gap-2.5 text-body text-ink">
                <input type="checkbox" name="declaration" value="1" required @checked(old('declaration'))
                    class="mt-1 h-4 w-4 rounded-control border-line accent-primary" />
                <span>Saya Menyatakan Informasi Dalam Laporan Ini Benar Adanya dan Dapat
                    Dipertanggungjawabkan.<span class="text-primary">*</span></span>
            </label>

            <div class="mt-7 grid grid-cols-2 gap-4">
                <a href="{{ route('home') }}"
                    class="rounded-control border-[0.5px] border-line px-5 py-3 text-center text-body font-semibold text-ink transition hover:border-black/40">
                    Batal
                </a>

                <button type="submit"
                    class="rounded-control bg-primary px-5 py-3 text-body font-semibold text-white transition hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                    Lapor
                </button>
            </div>
        </form>
    </main>
@endsection
