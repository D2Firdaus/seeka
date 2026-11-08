@props(['categories', 'locations'])

@php
    $typeValues = array_filter((array) request('type'));
@endphp

<input type="checkbox" id="filter-modal-toggle" class="peer sr-only" />

<label for="filter-modal-toggle"
    class="invisible fixed inset-0 z-50 cursor-pointer bg-black/40 opacity-0 [transition:opacity_100ms_ease-out,visibility_10ms] peer-checked:visible peer-checked:opacity-100"
    aria-hidden="true"></label>

<aside
    class="invisible fixed right-0 top-0 z-50 flex h-full w-full max-w-sm translate-x-full flex-col bg-white opacity-0 shadow-xl [transition:translate_300ms_ease-out,opacity_150ms_ease-out,visibility_300ms] peer-checked:visible peer-checked:translate-x-0 peer-checked:opacity-100">
    <div class="flex items-center justify-between border-b border-black/10 px-5 py-4">
        <h2 class="text-subtitle font-display">Filter</h2>
        <label for="filter-modal-toggle" class="cursor-pointer p-1" aria-label="Tutup filter">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </label>
    </div>

    <div class="flex-1 overflow-y-auto">
        <div class="border-b border-black/10 px-5 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-label">Kategori</h3>
                <button type="button" data-reset="category"
                    class="text-label text-primary hover:underline">Reset</button>
            </div>
            <div class="relative mt-3">
                <select name="category"
                    class="w-full appearance-none rounded-control border-[0.5px] border-line bg-white py-2.5 pl-3 pr-10 text-body outline-none has-[option[value='']:checked]:text-black/40 focus:border-primary">
                    <option value="" class="text-ink">Semua Kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->category_id }}" class="text-ink"
                            @selected(request('category') == $category->category_id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <x-icons.chevron-right
                    class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-ink" />
            </div>
        </div>

        <div class="border-b border-black/10 px-5 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-label">Lokasi</h3>
                <button type="button" data-reset="location"
                    class="text-label text-primary hover:underline">Reset</button>
            </div>
            <div class="relative mt-3">
                <select name="location"
                    class="w-full appearance-none rounded-control border-[0.5px] border-line bg-white py-2.5 pl-3 pr-10 text-body outline-none has-[option[value='']:checked]:text-black/40 focus:border-primary">
                    <option value="" class="text-ink">Semua Lokasi</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->location_id }}" class="text-ink"
                            @selected(request('location') == $location->location_id)>{{ $location->name }}</option>
                    @endforeach
                </select>
                <x-icons.chevron-right
                    class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-ink" />
            </div>
        </div>

        <div class="border-b border-black/10 px-5 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-label">Tanggal</h3>
                <button type="button" data-reset="date"
                    class="text-label text-primary hover:underline">Reset</button>
            </div>
            <div class="relative mt-3">
                <input type="text" id="date-range" readonly placeholder="Semua Tanggal"
                    class="w-full cursor-pointer appearance-none rounded-control border-[0.5px] border-line bg-white py-2.5 pl-3 pr-10 text-body outline-none placeholder:text-black/40 focus:border-primary" />
                <x-icons.chevron-right
                    class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-ink" />
                <input type="hidden" name="date_from" value="{{ request('date_from') }}" />
                <input type="hidden" name="date_to" value="{{ request('date_to') }}" />
            </div>
        </div>

        <div class="border-b border-black/10 px-5 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-label">Tipe</h3>
                <button type="button" data-reset="type"
                    class="text-label text-primary hover:underline">Reset</button>
            </div>
            <div class="mt-3 space-y-2">
                <label class="flex items-center gap-2.5 text-body">
                    <input type="checkbox" name="type[]" value="lost" @checked(in_array('lost', $typeValues, true))
                        class="h-4 w-4 rounded-control border-line accent-primary" />
                    Kehilangan
                </label>
                <label class="flex items-center gap-2.5 text-body">
                    <input type="checkbox" name="type[]" value="found" @checked(in_array('found', $typeValues, true))
                        class="h-4 w-4 rounded-control border-line accent-primary" />
                    Penemuan
                </label>
            </div>
        </div>

        <div class="border-b border-black/10 px-5 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-label">Status</h3>
                <button type="button" data-reset="status"
                    class="text-label text-primary hover:underline">Reset</button>
            </div>
            <div class="relative mt-3">
                <select name="status"
                    class="w-full appearance-none rounded-control border-[0.5px] border-line bg-white py-2.5 pl-3 pr-10 text-body outline-none has-[option[value='']:checked]:text-black/40 focus:border-primary">
                    <option value="" class="text-ink">Semua Status</option>
                    <option value="open" class="text-ink" @selected(request('status') == 'open')>Terbuka</option>
                    <option value="claimed" class="text-ink" @selected(request('status') == 'claimed')>Diklaim</option>
                    <option value="returned" class="text-ink" @selected(request('status') == 'returned')>Dikembalikan
                    </option>
                    <option value="closed" class="text-ink" @selected(request('status') == 'closed')>Ditutup</option>
                </select>
                <x-icons.chevron-right
                    class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-ink" />
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between gap-3 border-t border-black/10 px-5 py-4">
        <button type="button" data-reset="all"
            class="rounded-control border-[0.5px] border-line px-5 py-2.5 text-label transition hover:border-black/40">
            Reset All
        </button>
        <button type="submit"
            class="flex-1 rounded-control bg-primary px-5 py-2.5 text-label text-white transition hover:bg-primary/90">
            Terapkan
        </button>
    </div>
</aside>