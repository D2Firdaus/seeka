@extends('layouts.guest')

@section('title', 'Email Terverifikasi ' . config('app.name', 'SEEKA'))

@section('shell', 'max-w-2xl')

@section('content')
    <main class="flex flex-1 items-center">
        <div class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-8">
            <div class="mx-auto flex w-full max-w-2xl flex-col items-center text-center">
                <x-icons.email-success class="h-16 w-16 lg:h-24 lg:w-24" />

                <h1 class="mt-6 text-title">
                    Email Terverifikasi
                </h1>

                <p class="mt-4 text-body text-pretty text-muted">
                    Email anda berhasil terverifikasi, anda akan dialihkan ke halaman utama
                </p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-x-4 text-body">
                    <span class="text-muted">Dalam <span class="font-semibold text-primary"><span id="countdown-seconds">5</span> Detik</span></span>

                    <span class="text-subtle">atau</span>

                    <a href="{{ route('home') }}"
                        class="inline-flex items-center gap-1 font-semibold text-primary transition hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        Lanjut ke Beranda
                        <x-icons.chevron-right class="h-3 w-3" />
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script>
        (function () {
            const homeUrl = @json(route('home'));
            const seconds = document.getElementById('countdown-seconds');
            let remaining = 5;

            const timer = setInterval(function () {
                remaining -= 1;
                seconds.textContent = remaining;

                if (remaining <= 0) {
                    clearInterval(timer);
                    window.location.assign(homeUrl);
                }
            }, 1000);
        })();
    </script>
@endsection
