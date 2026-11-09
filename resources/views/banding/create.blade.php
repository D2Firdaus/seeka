@extends('layouts.app')

@section('title', 'Ajukan Banding — ' . config('app.name', 'SEEKA'))

@section('content')
    <main class="mx-auto w-full max-w-7xl flex-1 px-5 pb-16 pt-8 sm:px-8">
        <header class="mb-8">
            <h1 class="text-display font-display">
                Ajukan Banding
            </h1>

            <p class="mt-3 text-title font-display font-normal text-ink-soft">
                Sanggah status klaim barang dan ajukan peninjauan ulang kepemilikan
            </p>
        </header>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,42rem)_1fr] lg:items-start lg:gap-12">

            {{-- FORM: mobile di bawah kartu, desktop di kiri --}}
            <div class="w-full max-w-2xl order-2 lg:order-1">
                @if ($errors->any())
                    <x-alert type="error" class="mb-6">{{ $errors->first() }}</x-alert>
                @endif

                <div class="mb-6 rounded-control border-[0.5px] border-line bg-surface p-4 text-body-sm text-ink-soft">
                    <p class="font-semibold text-ink">Informasi Status Barang</p>
                    <p class="mt-1">
                        Barang ini saat ini tercatat berstatus <span class="font-semibold text-ink">{{ $item->status === 'returned' ? 'Dikembalikan' : 'Diklaim' }}</span>.
                        Jika Anda meyakini barang ini milik Anda dan bukan milik pihak yang telah mengklaimnya, silakan sampaikan alasan dan sanggahan Anda dengan jelas.
                    </p>
                </div>

                <form method="POST" action="{{ route('banding.store', $item) }}">
                    @csrf

                    <div>
                        <label for="reason" class="mb-2 block text-label text-ink">
                            Alasan Banding &amp; Bukti Sanggahan<span class="text-primary">*</span>
                        </label>
                        <textarea id="reason" name="reason" rows="7" required
                            placeholder="Jelaskan secara rinci mengapa Anda meyakini barang ini milik Anda dan bukan milik pihak yang telah mengklaimnya (misal: nomor identitas, bukti pembelian, waktu kehilangan sebelum klaim, ciri unik yang luput dari verifikasi)."
                            class="w-full resize-none rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-black/40 focus:border-primary">{{ old('reason') }}</textarea>
                        <p class="mt-1.5 text-caption text-muted">
                            Petugas akan meninjau ulang berkas klaim dan menghubungi pihak-pihak terkait untuk klarifikasi.
                        </p>
                    </div>

                    <label class="mt-6 flex items-start gap-2.5 text-body text-ink">
                        <input type="checkbox" name="declaration" value="1" required @checked(old('declaration'))
                            class="mt-1 h-4 w-4 rounded-control border-line accent-primary" />
                        <span>
                            Saya menyatakan bahwa informasi sanggahan dan alasan banding ini adalah benar dan dapat dipertanggungjawabkan.<span class="text-primary">*</span>
                        </span>
                    </label>

                    {{-- Alur Banding: hanya tampil di mobile, tepat sebelum tombol --}}
                    <x-alur-banding class="mt-8 lg:hidden" />

                    <div class="mt-8 grid grid-cols-1 min-[380px]:grid-cols-2 gap-4">
                        <a href="{{ route('home') }}"
                            class="flex items-center justify-center rounded-control border-[0.5px] border-line px-5 py-4 text-center text-label text-ink transition hover:border-black/40">
                            Batal
                        </a>

                        <button type="submit"
                            class="rounded-control bg-primary px-5 py-4 text-body font-semibold text-white transition hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                            Kirim Banding
                        </button>
                    </div>
                </form>
            </div>

            {{-- ASIDE: mobile di atas form, desktop di kanan --}}
            <aside class="flex w-full flex-col gap-6 order-1 lg:order-2 lg:sticky lg:top-6">

                <div>
                    <p class="mb-2 text-label text-ink">Barang yang Diajukan Banding</p>

                    <div class="grid grid-cols-1">
                        <x-item-card :item="$item" :show-action="false" />
                    </div>
                </div>

                {{-- Alur Banding: hanya tampil di desktop --}}
                <x-alur-banding class="hidden lg:block" />
            </aside>
        </div>
    </main>
@endsection
