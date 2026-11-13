@extends('layouts.app')

@section('title', ($category ? 'Edit' : 'Tambah') . ' Kategori — ' . config('app.name', 'SEEKA'))

@php
    $inputClass = 'w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-faint hover:border-black/40 focus:border-primary';
@endphp

@section('content')
    <main class="mx-auto w-full max-w-3xl flex-1 px-5 pb-16 pt-8 sm:px-8">
        <h1 class="text-display font-display">{{ $category ? 'Edit Kategori' : 'Tambah Kategori' }}</h1>

        @if ($errors->any())
            <x-alert type="error" class="mt-6">{{ $errors->first() }}</x-alert>
        @endif

        <section class="mt-6 rounded-control border-[0.5px] border-line bg-white p-5 sm:p-6">
            <form method="POST"
                action="{{ $category ? route('admin.metadata.kategori.update', $category) : route('admin.metadata.kategori.store') }}"
                class="grid gap-4">
                @csrf
                @if ($category)
                    @method('PUT')
                @endif

                <div>
                    <label for="name" class="block text-label text-ink-soft">Nama Kategori<span
                            class="text-primary">*</span></label>
                    <input id="name" name="name" type="text" value="{{ old('name', $category?->name) }}" required autofocus
                        placeholder="Nama Kategori" maxlength="100"
                        class="mt-1.5 {{ $inputClass }}" />
                </div>

                <div class="flex items-center gap-5">
                    <button type="submit"
                        class="rounded-control bg-primary px-5 py-2.5 text-label text-white transition hover:bg-primary/90">
                        Simpan
                    </button>

                    <a href="{{ route('admin.metadata.kategori') }}"
                        class="text-label text-primary transition hover:underline">
                        Batal
                    </a>
                </div>
            </form>
        </section>
    </main>
@endsection
