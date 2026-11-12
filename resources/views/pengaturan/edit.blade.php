@extends('layouts.app')

@section('title', 'Pengaturan — ' . config('app.name', 'SEEKA'))

@php
    $passwordInputClass = 'w-full rounded-control border-[0.5px] border-line bg-white py-3 pl-3.5 pr-14 text-body text-ink outline-none transition placeholder:text-faint hover:border-black/40 focus:border-primary';
    $checkboxClass = 'size-4 shrink-0 rounded-control border-[0.5px] border-line accent-primary';
@endphp

@section('content')
    <main class="mx-auto w-full max-w-3xl flex-1 px-5 pb-16 pt-8 sm:px-8">
        <h1 class="text-display font-display">Pengaturan</h1>

        @if ($errors->any())
            <x-alert type="error" class="mt-6">{{ $errors->first() }}</x-alert>
        @endif

        <section class="mt-6 rounded-control border-[0.5px] border-line bg-white p-5 sm:p-6">
            <h2 class="text-title font-display">Notifikasi</h2>
            <p class="mt-1 text-body-sm text-muted">
                Pilih kanal pemberitahuan untuk kabar klaim dan serah terima Anda.
            </p>

            <form method="POST" action="{{ route('pengaturan.notifikasi') }}" class="mt-5 grid gap-4">
                @csrf
                @method('PATCH')

                <label class="flex items-start gap-3">
                    <input type="hidden" name="notify_email" value="0" />
                    <input type="checkbox" name="notify_email" value="1" @checked(old('notify_email', $user->notify_email))
                        class="mt-1 {{ $checkboxClass }}" />
                    <span>
                        <span class="block text-label text-ink">Email</span>
                        <span class="mt-0.5 block text-body-sm text-muted">
                            Kirim pemberitahuan status klaim ke {{ $user->email }}.
                        </span>
                    </span>
                </label>

                <label class="flex items-start gap-3">
                    <input type="hidden" name="notify_whatsapp" value="0" />
                    <input type="checkbox" name="notify_whatsapp" value="1" @checked(old('notify_whatsapp', $user->notify_whatsapp))
                        class="mt-1 {{ $checkboxClass }}" />
                    <span>
                        <span class="block text-label text-ink">WhatsApp</span>
                        <span class="mt-0.5 block text-body-sm text-muted">
                            Kirim pemberitahuan ke nomor {{ $user->phone ?: 'HP yang belum diisi' }}.
                        </span>
                    </span>
                </label>

                <div>
                    <button type="submit"
                        class="rounded-control bg-primary px-5 py-2.5 text-label text-white transition hover:bg-primary/90">
                        Simpan
                    </button>
                </div>
            </form>
        </section>

        @if (blank($user->google_id))
            <section class="mt-6 rounded-control border-[0.5px] border-line bg-white p-5 sm:p-6">
                <h2 class="text-title font-display">Ganti Kata Sandi</h2>
                <p class="mt-1 text-body-sm text-muted">
                    Masukkan kata sandi saat ini sebelum menggantinya dengan yang baru.
                </p>

                <form method="POST" action="{{ route('pengaturan.password') }}" class="mt-5 grid gap-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="current-password" class="block text-label text-ink-soft">Kata Sandi Saat Ini</label>
                        <div class="relative mt-1.5">
                            <input id="current-password" name="current_password" type="password" required autocomplete="current-password"
                                class="{{ $passwordInputClass }}" />
                            <button type="button" data-toggle-password="current-password" aria-label="Tampilkan kata sandi"
                                aria-pressed="false"
                                class="group absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-control bg-primary text-white transition hover:bg-primary/90">
                                <x-icons.eye-closed
                                    class="absolute h-5 w-5 transition duration-300 ease-out group-aria-pressed:scale-75 group-aria-pressed:opacity-0" />
                                <x-icons.eye-open
                                    class="absolute h-5 w-5 scale-75 opacity-0 transition duration-300 ease-out group-aria-pressed:scale-100 group-aria-pressed:opacity-100" />
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-label text-ink-soft">Kata Sandi Baru</label>
                        <div class="relative mt-1.5">
                            <input id="password" name="password" type="password" required autocomplete="new-password"
                                class="{{ $passwordInputClass }}" />
                            <button type="button" data-toggle-password="password" aria-label="Tampilkan kata sandi"
                                aria-pressed="false"
                                class="group absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-control bg-primary text-white transition hover:bg-primary/90">
                                <x-icons.eye-closed
                                    class="absolute h-5 w-5 transition duration-300 ease-out group-aria-pressed:scale-75 group-aria-pressed:opacity-0" />
                                <x-icons.eye-open
                                    class="absolute h-5 w-5 scale-75 opacity-0 transition duration-300 ease-out group-aria-pressed:scale-100 group-aria-pressed:opacity-100" />
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="password-confirmation" class="block text-label text-ink-soft">Konfirmasi Kata Sandi Baru</label>
                        <div class="relative mt-1.5">
                            <input id="password-confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                                class="{{ $passwordInputClass }}" />
                            <button type="button" data-toggle-password="password-confirmation" aria-label="Tampilkan kata sandi"
                                aria-pressed="false"
                                class="group absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-control bg-primary text-white transition hover:bg-primary/90">
                                <x-icons.eye-closed
                                    class="absolute h-5 w-5 transition duration-300 ease-out group-aria-pressed:scale-75 group-aria-pressed:opacity-0" />
                                <x-icons.eye-open
                                    class="absolute h-5 w-5 scale-75 opacity-0 transition duration-300 ease-out group-aria-pressed:scale-100 group-aria-pressed:opacity-100" />
                            </button>
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                            class="rounded-control bg-primary px-5 py-2.5 text-label text-white transition hover:bg-primary/90">
                            Perbarui Kata Sandi
                        </button>
                    </div>
                </form>
            </section>
        @endif
    </main>
@endsection
