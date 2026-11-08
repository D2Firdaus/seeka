@extends('layouts.app')

@section('title', 'Laporan Kehilangan ' . config('app.name', 'SEEKA'))

@section('content')
    <main class="mx-auto w-full max-w-2xl flex-1 px-5 pb-16 pt-8 sm:px-8">
        <h1 class="text-title">
            Buat Laporan Kehilangan
        </h1>

        <p class="mt-4 text-subtitle text-balance text-muted">
            Ceritakan Barang yang Hilang Agar Dapat Dicocokkan dengan Laporan Penemuan
        </p>

        @if ($errors->any())
            <x-alert type="error" class="mt-7">{{ $errors->first() }}</x-alert>
        @endif

        <form method="POST" action="{{ route('lapor.kehilangan') }}" enctype="multipart/form-data" class="mt-8">
            @csrf

            <div>
                <label for="title" class="mb-1.5 block text-body text-ink-soft">Judul Barang</label>
                <input id="title" name="title" type="text" value="{{ old('title') }}" required autofocus
                    placeholder="Contoh: Dompet hitam merek Eiger"
                    class="w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-faint hover:border-black/40 focus:border-primary" />
            </div>

            <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="category_id" class="mb-1.5 block text-body text-ink-soft">Kategori</label>
                    <select id="category_id" name="category_id" required
                        class="w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition hover:border-black/40 focus:border-primary">
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->category_id }}" @selected(old('category_id') == $category->category_id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="location_id" class="mb-1.5 block text-body text-ink-soft">Lokasi Kejadian</label>
                    <select id="location_id" name="location_id" required
                        class="w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition hover:border-black/40 focus:border-primary">
                        <option value="">Pilih lokasi</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->location_id }}" @selected(old('location_id') == $location->location_id)>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-5">
                <label for="date_event" class="mb-1.5 block text-body text-ink-soft">Tanggal Kejadian</label>
                <input id="date_event" name="date_event" type="date" value="{{ old('date_event') }}" required
                    max="{{ now()->toDateString() }}"
                    class="w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition hover:border-black/40 focus:border-primary" />
            </div>

            <div class="mt-5">
                <label for="description" class="mb-1.5 block text-body text-ink-soft">Deskripsi</label>
                <textarea id="description" name="description" rows="4" required
                    placeholder="Cirinya, isi kantong, atau hal lain yang membantu mengenali barang"
                    class="w-full resize-none rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-faint hover:border-black/40 focus:border-primary">{{ old('description') }}</textarea>
            </div>

            <div class="mt-5">
                <label for="photos" class="mb-1.5 block text-body text-ink-soft">Foto Barang
                    <span class="text-muted">(opsional, maksimal 5)</span>
                </label>
                <input id="photos" name="photos[]" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" multiple
                    class="w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-2.5 text-body text-ink outline-none transition file:mr-3 file:rounded-control file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:font-semibold file:text-primary hover:border-black/40 focus:border-primary" />
                <p class="mt-1.5 text-caption text-muted">Format JPG atau PNG, maksimal 2 MB per foto.</p>
            </div>

            <button type="submit"
                class="mt-7 w-full rounded-control bg-primary px-5 py-3.5 text-body font-semibold text-white transition hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                Kirim Laporan
            </button>
        </form>
    </main>
@endsection
