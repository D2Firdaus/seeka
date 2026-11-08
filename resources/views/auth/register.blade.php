@extends('layouts.guest')

@section('title', 'Buat Akun ' . config('app.name', 'SEEKA'))

@section('content')
    <main class="flex flex-1 items-start lg:items-center">
        <div class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-8 lg:py-10">
            <div class="mx-auto max-w-[440px]">
                <h1 class="text-display font-display">
                    Buat Akun SEEKA
                </h1>

                <p class="mt-4 text-title font-display font-normal text-muted">
                    Bantu Barang Kembali Kepada Pemiliknya
                </p>

                @if ($errors->any())
                    <x-alert type="error" class="mt-7">{{ $errors->first() }}</x-alert>
                @endif

                <form method="POST" action="{{ route('register') }}" class="mt-8">
                    @csrf

                    <div>
                        <label for="name" class="mb-1.5 block text-label text-ink-soft">Nama Lengkap</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                            autocomplete="name" placeholder="Masukkan nama lengkap"
                            class="w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-faint hover:border-black/40 focus:border-primary" />
                    </div>

                    <div class="mt-5">
                        <label for="email" class="mb-1.5 block text-label text-ink-soft">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                            autocomplete="username" placeholder="Masukkan email unsika"
                            class="w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-faint hover:border-black/40 focus:border-primary" />
                    </div>

                    <div class="mt-5">
                        <label for="password" class="mb-1.5 block text-label text-ink-soft">Kata Sandi</label>
                        <div class="relative">
                            <input id="password" name="password" type="password" required autocomplete="new-password"
                                placeholder="Minimal 8 karakter"
                                class="w-full rounded-control border-[0.5px] border-line bg-white py-3 pl-3.5 pr-14 text-body text-ink outline-none transition placeholder:text-faint hover:border-black/40 focus:border-primary" />
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

                    <div class="mt-5">
                        <label for="password_confirmation" class="mb-1.5 block text-label text-ink-soft">Konfirmasi Kata
                            Sandi</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            autocomplete="new-password" placeholder="Ulangi kata sandi"
                            class="w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-faint hover:border-black/40 focus:border-primary" />
                    </div>

                    <label class="mt-5 flex items-start gap-2.5 text-body text-ink">
                        <input type="checkbox" name="terms" value="1" @checked(old('terms'))
                            class="mt-1 h-4 w-4 rounded-control border-[0.5px] border-line accent-primary" />
                        <span>Saya Menyetujui Syarat dan Ketentuan serta Kebijakan Privasi</span>
                    </label>

                    <button type="submit"
                        class="mt-7 w-full rounded-control bg-primary px-5 py-3.5 text-label text-white transition hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        Daftar
                    </button>
                </form>

                <p class="mt-6 text-center text-body text-muted">
                    Sudah Punya Akun?
                    <a href="{{ route('login') }}" class="font-semibold text-primary underline-offset-4 hover:underline">
                        Masuk
                    </a>
                </p>
            </div>
        </div>
    </main>
@endsection