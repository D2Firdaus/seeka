@extends('layouts.app')

@section('title', 'Kelola Pengguna — ' . config('app.name', 'SEEKA'))

@section('content')
    <main class="mx-auto w-full max-w-7xl flex-1 px-5 pb-16 pt-6 sm:px-8">
        <h1 class="text-display font-display mb-[1.15em]">Kelola Pengguna</h1>

        {{-- Form Pencarian & Filter --}}        <form method="GET" action="{{ route('admin.users') }}" class="mt-6 mb-5">
            <div class="flex flex-wrap items-stretch gap-3">
                <div class="flex h-11 min-w-[12rem] flex-1 items-stretch lg:h-[3.125rem]">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama, email, atau NIM/NIP..."
                        class="w-full min-w-0 rounded-l-control border-[0.5px] border-r-0 border-line bg-white px-3.5 py-3 text-body outline-none transition hover:border-black/40 focus:border-primary" />

                    <button type="submit"
                        class="flex shrink-0 items-center justify-center gap-2 rounded-r-control bg-primary px-5 py-3 text-label text-white transition hover:bg-primary/90">
                        <x-icons.search class="h-5 w-5" />
                        <span class="hidden sm:inline">Cari</span>
                    </button>
                </div>

                <div class="relative flex h-11 items-stretch lg:h-[3.125rem]">
                    <select name="role"
                        class="h-full w-full appearance-none rounded-control border-[0.5px] border-line bg-white py-3 pl-3.5 pr-10 text-body text-ink outline-none transition hover:border-black/40 focus:border-primary">
                        <option value="" class="text-ink">Semua Peran</option>
                        <option value="admin" class="text-ink" @selected(request('role') === 'admin')>Admin</option>
                        <option value="user" class="text-ink" @selected(request('role') === 'user')>Pengguna</option>
                    </select>
                    <x-icons.chevron-right
                        class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 rotate-90 text-ink" />
                </div>

                <a href="{{ route('admin.users.create') }}"
                    class="flex h-11 shrink-0 items-center justify-center rounded-control bg-primary px-5 text-label text-white transition hover:bg-primary/90 lg:h-[3.125rem]">
                    Tambah Pengguna
                </a>
            </div>
        </form>

        {{-- Filter Aktif & Jumlah Data --}}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 text-body-sm text-muted">
            <p>
                Menampilkan {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} pengguna
            </p>

            @if (request()->hasAny(['q', 'role']))
                <a href="{{ route('admin.users') }}"
                    class="text-caption font-semibold text-primary transition hover:underline">
                    Reset Filter
                </a>
            @endif
        </div>

        {{-- List Pengguna --}}
        <ul class="divide-y divide-black/10 rounded-control border-[0.5px] border-line bg-white">
            @forelse ($users as $user)
                <li class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:gap-4 transition hover:bg-black/[0.015]">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-control bg-black/5 text-muted">
                            <x-icons.profile class="h-5 w-5 opacity-60" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-body font-semibold text-ink">
                                {{ $user->name }}
                                @if ($user->user_id === auth()->id())
                                    <span class="text-caption font-normal text-muted">(Anda)</span>
                                @endif
                            </p>
                            <p class="truncate text-body-sm text-muted">
                                {{ $user->email }}
                                @if ($user->nim_nip)
                                    · {{ $user->nim_nip }}
                                @endif
                                @if ($user->phone)
                                    · {{ $user->phone }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        <span @class([
                            'inline-flex items-center rounded-control border-[0.5px] px-2.5 py-0.5 text-caption font-semibold',
                            'bg-primary border-primary text-surface' => $user->role === 'admin',
                            'bg-closed border-closed text-surface' => $user->role !== 'admin',
                        ])>
                            {{ $user->role === 'admin' ? 'Admin' : 'Pengguna' }}
                        </span>

                        <div class="flex items-center gap-1 pl-1 sm:border-l sm:border-black/10 sm:pl-3">
                            <a href="{{ route('admin.users.edit', $user) }}"
                                class="flex h-8 w-8 items-center justify-center rounded-control text-ink-soft transition hover:bg-black/5 hover:text-primary"
                                title="Edit Pengguna"
                                aria-label="Edit Pengguna">
                                <x-icons.edit class="h-5 w-5" />
                            </a>

                            @if ($user->user_id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                    onsubmit="return confirm('Hapus pengguna ini? Tindakan ini tidak dapat dibatalkan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="flex h-8 w-8 items-center justify-center rounded-control text-ink-soft transition hover:bg-primary/5 hover:text-primary"
                                        title="Hapus Pengguna"
                                        aria-label="Hapus Pengguna">
                                        <x-icons.trash class="h-5 w-5" />
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </li>
            @empty
                <li class="p-8 text-center text-body text-muted">
                    @if (request()->hasAny(['q', 'role']))
                        Belum ada pengguna yang cocok dengan filter yang dipilih.
                    @else
                        Belum ada pengguna.
                    @endif
                </li>
            @endforelse
        </ul>

        {{-- Pagination --}}
        @if ($users->hasPages())
            <div class="mt-6">
                {{ $users->links('pagination.compact') }}
            </div>
        @endif
    </main>
@endsection
