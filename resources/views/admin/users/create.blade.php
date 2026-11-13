@extends('layouts.app')

@section('title', 'Tambah Pengguna — ' . config('app.name', 'SEEKA'))

@php
    $inputClass = 'w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-faint hover:border-black/40 focus:border-primary';
@endphp

@section('content')
    <main class="mx-auto w-full max-w-3xl flex-1 px-5 pb-16 pt-8 sm:px-8">
        <h1 class="text-display font-display">Tambah Pengguna</h1>

        @if ($errors->any())
            <x-alert type="error" class="mt-6">{{ $errors->first() }}</x-alert>
        @endif

        <section class="mt-6 rounded-control border-[0.5px] border-line bg-white p-5 sm:p-6">
            <h2 class="text-title font-display">Data Pengguna</h2>
            <p class="mt-1 text-body-sm text-muted">
                Isi data akun baru. Kata sandi akan digunakan pengguna untuk masuk.
            </p>

            <form method="POST" action="{{ route('admin.users.store') }}" class="mt-5 grid gap-4">
                @csrf

                <div>
                    <label for="name" class="block text-label text-ink-soft">Nama Lengkap</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                        autocomplete="name" class="mt-1.5 {{ $inputClass }}" />
                </div>

                <div>
                    <label for="email" class="block text-label text-ink-soft">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                        autocomplete="username" class="mt-1.5 {{ $inputClass }}" />
                </div>

                <div>
                    <label for="nim_nip" class="block text-label text-ink-soft">
                        NIM/NIP
                        <span class="text-caption font-normal text-muted">(Opsional)</span>
                    </label>
                    <input id="nim_nip" name="nim_nip" type="text" value="{{ old('nim_nip') }}"
                        class="mt-1.5 {{ $inputClass }}" />
                </div>

                <div>
                    <label for="phone" class="block text-label text-ink-soft">
                        Nomor HP
                        <span class="text-caption font-normal text-muted">(Opsional)</span>
                    </label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                        autocomplete="tel" class="mt-1.5 {{ $inputClass }}" />
                </div>

                <div>
                    <label for="role" class="block text-label text-ink-soft">Peran</label>
                    <div class="relative mt-1.5">
                        <select id="role" name="role" required
                            class="w-full appearance-none rounded-control border-[0.5px] border-line bg-white py-3 pl-3.5 pr-10 text-body text-ink outline-none transition hover:border-black/40 focus:border-primary">
                            <option value="user" class="text-ink" @selected(old('role', 'user') === 'user')>Pengguna</option>
                            <option value="admin" class="text-ink" @selected(old('role') === 'admin')>Admin</option>
                        </select>
                        <x-icons.chevron-right
                            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-ink" />
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-label text-ink-soft">Kata Sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                        class="mt-1.5 {{ $inputClass }}" />
                </div>

                <div>
                    <label for="password_confirmation" class="block text-label text-ink-soft">Konfirmasi Kata Sandi</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                        autocomplete="new-password" class="mt-1.5 {{ $inputClass }}" />
                </div>

                <div class="grid grid-cols-1 gap-4 pt-2 min-[380px]:grid-cols-2">
                    <a href="{{ route('admin.users') }}"
                        class="flex items-center justify-center rounded-control border-[0.5px] border-line px-5 py-3 text-center text-label text-ink transition hover:border-black/40">
                        Batal
                    </a>

                    <button type="submit"
                        class="rounded-control bg-primary px-5 py-3 text-label text-white transition hover:bg-primary/90">
                        Simpan Pengguna
                    </button>
                </div>
            </form>
        </section>
    </main>
@endsection
