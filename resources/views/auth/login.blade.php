@extends('layouts.guest')

@section('title', 'Masuk ' . config('app.name', 'SEEKA'))

@section('content')
    <main class="flex flex-1 items-start lg:items-center">
        <div class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-8 lg:py-10">
            <div class="mx-auto max-w-[440px]">
                <h1 class="text-title">
                    Login SEEKA
                </h1>

                <p class="mt-4 text-subtitle text-balance text-muted">
                    Bantu barang kembali kepada pemiliknya.
                </p>

                @if ($errors->any())
                    <x-alert type="error" class="mt-7">{{ $errors->first() }}</x-alert>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-8">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-body text-ink-soft">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                            autocomplete="username" placeholder="Masukkan email unsika"
                            class="w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-faint hover:border-black/40 focus:border-primary" />
                    </div>

                    <div class="mt-5">
                        <div class="mb-1.5 flex items-baseline justify-between gap-3">
                            <label for="password" class="text-body text-ink-soft">Kata Sandi</label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}"
                                    class="text-body font-medium text-primary underline-offset-4 hover:underline">
                                    Lupa kata sandi?
                                </a>
                            @endif
                        </div>
                        <div class="relative">
                            <input id="password" name="password" type="password" required autocomplete="current-password"
                                placeholder="Masukkan kata sandi"
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

                    <button type="submit"
                        class="mt-7 w-full rounded-control bg-primary px-5 py-3.5 text-body font-semibold text-white transition hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        Masuk
                    </button>
                </form>

                <div class="mt-6 flex items-center gap-3">
                    <span class="h-px flex-1 bg-black/10"></span>
                    <span class="text-caption text-muted">Atau masuk dengan</span>
                    <span class="h-px flex-1 bg-black/10"></span>
                </div>

                <a href="{{ route('google.login') }}"
                    class="mt-4 flex w-full items-center justify-center gap-2.5 rounded-control border-[0.5px] border-line bg-white px-5 py-3.5 text-body font-semibold text-ink transition hover:bg-black/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#4285F4"
                            d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.86c2.26-2.09 3.56-5.17 3.56-8.87z" />
                        <path fill="#34A853"
                            d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09A11.99 11.99 0 0 0 12 24z" />
                        <path fill="#FBBC05"
                            d="M5.27 14.29a7.17 7.17 0 0 1 0-4.58V6.62H1.29a12 12 0 0 0 0 10.76l3.98-3.09z" />
                        <path fill="#EA4335"
                            d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0A11.99 11.99 0 0 0 1.29 6.62l3.98 3.09C6.22 6.86 8.87 4.75 12 4.75z" />
                    </svg>
                    Masuk dengan Google
                </a>

                <p class="mt-6 text-center text-body text-muted">
                    Belum punya akun?
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="font-semibold text-primary underline-offset-4 hover:underline">
                            Daftar
                        </a>
                    @else
                        <span class="font-semibold text-ink-soft">Daftar</span>
                    @endif
                </p>
            </div>
        </div>
    </main>
@endsection
