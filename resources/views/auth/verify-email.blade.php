@extends('layouts.guest')

@section('title', 'Verifikasi Email Anda ' . config('app.name', 'SEEKA'))

@section('shell', 'max-w-2xl')

@section('content')
    <main class="flex flex-1 items-center">
        <div class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-8">
            <div class="mx-auto flex w-full max-w-2xl flex-col items-center text-center">
                <x-icons.email-send class="h-16 w-16 lg:h-24 lg:w-24" />

                <h1 class="mt-6 text-title">
                    Verifikasi Email Anda
                </h1>

                <p class="mt-4 text-body text-pretty text-muted">
                    Kami telah mengirimkan tautan verifikasi ke
                    <span class="font-semibold text-primary">{{ $email }}</span>
                </p>

                <p class="mt-3 text-body text-pretty text-muted">
                    Klik tautan tersebut untuk menyelesaikan proses verifikasi. Anda mungkin perlu memeriksa
                    <span class="font-semibold text-ink">folder spam</span> Anda.
                </p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-3">
                    <form method="POST" action="{{ route('verification.resend') }}">
                        @csrf

                        <button type="submit" data-resend-cooldown="{{ $cooldown }}"
                            @disabled($cooldown > 0)
                            class="rounded-control bg-primary px-5 py-3 text-body font-semibold text-white transition hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:pointer-events-none disabled:opacity-60">
                            <span data-resend-label>
                                {{ $cooldown > 0 ? "Kirim ulang dalam {$cooldown} detik" : 'Kirim Ulang Email' }}
                            </span>
                        </button>
                    </form>

                    <a href="{{ route('home') }}"
                        class="inline-flex items-center gap-1 text-body font-semibold text-primary transition hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        Kembali Ke Beranda
                        <x-icons.chevron-right class="h-3 w-3" />
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script>
        (function () {
            const button = document.querySelector('[data-resend-cooldown]');

            if (!button) {
                return;
            }

            let remaining = parseInt(button.dataset.resendCooldown, 10);

            if (remaining <= 0) {
                return;
            }

            const label = button.querySelector('[data-resend-label]');

            const timer = setInterval(function () {
                remaining -= 1;

                if (remaining <= 0) {
                    clearInterval(timer);
                    button.disabled = false;
                    label.textContent = 'Kirim Ulang Email';
                    return;
                }

                label.textContent = 'Kirim ulang dalam ' + remaining + ' detik';
            }, 1000);
        })();
    </script>
@endsection
