@extends('layouts.guest')

@section('title', 'Link Verifikasi Kedaluwarsa ' . config('app.name', 'SEEKA'))

@section('shell', 'max-w-2xl')

@section('content')
    <main class="flex flex-1 items-center">
        <div class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-8">
            <div class="mx-auto flex w-full max-w-2xl flex-col items-center text-center">
                <img src="{{ asset('images/seeka-cry.png') }}" alt="Verifikasi kedaluwarsa"
                    class="h-32 w-auto lg:h-40" />

                <h1 class="mt-6 text-display">
                    Link Verifikasi Kedaluwarsa
                </h1>

                <p class="mt-4 text-body text-pretty text-muted">
                    Batas waktu verifikasi email Anda telah habis. Silakan klik tombol di bawah untuk mengirim
                    ulang link verifikasi yang baru.
                </p>

                <form method="POST" action="{{ route('verification.resend') }}" class="mt-8">
                    @csrf

                    <button type="submit"
                        class="rounded-control bg-primary px-6 py-3 text-body font-semibold text-white transition hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        Kirim Ulang Email
                    </button>
                </form>

                <p class="mt-4 text-body text-muted">
                    Tidak menerima email?
                    <a href="{{ route('register') }}" class="font-semibold text-primary underline-offset-4 hover:underline">
                        Daftar Ulang
                    </a>
                </p>
            </div>
        </div>
    </main>
@endsection
