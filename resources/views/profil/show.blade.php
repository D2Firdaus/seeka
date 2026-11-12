@extends('layouts.app')

@section('title', 'Profil — ' . config('app.name', 'SEEKA'))

@php
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->take(2)
        ->join('');

    $joinedLabel = $user->created_at->locale('id')->translatedFormat('j F Y');

    $readonlyClass = 'w-full cursor-not-allowed rounded-control border-[0.5px] border-line bg-black/5 px-3.5 py-3 text-body text-muted outline-none';
    $inputClass = 'w-full rounded-control border-[0.5px] border-line bg-white px-3.5 py-3 text-body text-ink outline-none transition placeholder:text-faint hover:border-black/40 focus:border-primary';
@endphp

@section('content')
    <main class="mx-auto w-full max-w-7xl flex-1 px-5 pb-16 pt-8 sm:px-8">
        <h1 class="text-display font-display mb-[1.15em]">Profil Saya</h1>

        <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:gap-8">
            <aside class="w-full shrink-0 lg:sticky lg:top-6 lg:self-start lg:w-72 xl:w-80">
                <input type="checkbox" id="profile-edit-toggle" class="peer sr-only" @checked($errors->any()) />

                <div class="flex items-center gap-4">
                    <div class="flex size-32 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary text-title font-display text-white"
                        aria-hidden="true">
                        {{ $initials }}
                    </div>

                    <div class="min-w-0">
                        <h2 class="break-words text-title font-display">{{ $user->name }}</h2>
                    </div>
                </div>

                <dl class="mt-5 grid gap-3 peer-checked:hidden">
                    <div>
                        <dt class="text-caption text-muted">Email</dt>
                        <dd class="break-all text-body text-ink">{{ $user->email }}</dd>
                    </div>

                    <div>
                        <dt class="text-caption text-muted">Verifikasi Email</dt>
                        <dd class="text-body {{ $user->hasVerifiedEmail() ? 'text-ink' : 'text-muted' }}">
                            {{ $user->hasVerifiedEmail() ? 'Terverifikasi' : 'Belum Diverifikasi' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-caption text-muted">NIM / NIP</dt>
                        <dd class="text-body text-ink">{{ $user->nim_nip ?: 'Belum diisi' }}</dd>
                    </div>

                    <div>
                        <dt class="text-caption text-muted">No. HP</dt>
                        <dd class="text-body text-ink">{{ $user->phone ?: 'Belum diisi' }}</dd>
                    </div>

                    <div>
                        <dt class="text-caption text-muted">Peran</dt>
                        <dd class="text-body text-ink">{{ $user->isAdmin() ? 'Admin' : 'Pengguna' }}</dd>
                    </div>

                    <div>
                        <dt class="text-caption text-muted">Bergabung Sejak</dt>
                        <dd class="text-body text-ink">{{ $joinedLabel }}</dd>
                    </div>
                </dl>

                <label for="profile-edit-toggle"
                    class="peer-checked:hidden mt-5 flex w-full cursor-pointer items-center justify-center rounded-control bg-primary px-5 py-2.5 text-label text-white transition hover:bg-primary/90">
                    Ubah Profil
                </label>

                <form method="POST" action="{{ route('profile.update') }}" class="mt-5 hidden gap-3 peer-checked:grid">
                    @csrf
                    @method('PATCH')

                    @if ($errors->any())
                        <x-alert type="error">{{ $errors->first() }}</x-alert>
                    @endif

                    <div>
                        <label for="profile-name" class="block text-label text-ink-soft">Nama</label>
                        <input id="profile-name" name="name" type="text" value="{{ old('name', $user->name) }}"
                            required class="mt-1.5 {{ $inputClass }}" />
                    </div>

                    <div>
                        <label for="profile-phone" class="block text-label text-ink-soft">No. HP</label>
                        <input id="profile-phone" name="phone" type="text"
                            value="{{ old('phone', $user->phone) }}" placeholder="6281234567890"
                            class="mt-1.5 {{ $inputClass }}" />
                    </div>

                    <div>
                        <label for="profile-email" class="block text-label text-ink-soft">Email</label>
                        <input id="profile-email" type="text" value="{{ $user->email }}" readonly
                            class="mt-1.5 {{ $readonlyClass }}" />
                    </div>

                    <div>
                        <label for="profile-verified" class="block text-label text-ink-soft">Verifikasi Email</label>
                        <input id="profile-verified" type="text"
                            value="{{ $user->hasVerifiedEmail() ? 'Terverifikasi' : 'Belum Diverifikasi' }}" readonly
                            class="mt-1.5 {{ $readonlyClass }}" />
                    </div>

                    <div>
                        <label for="profile-nim-nip" class="block text-label text-ink-soft">NIM / NIP</label>
                        <input id="profile-nim-nip" name="nim_nip" type="text"
                            value="{{ old('nim_nip', $user->nim_nip) }}" placeholder="Isi NIM/NIP"
                            @readonly(filled($user->nim_nip))
                            class="mt-1.5 {{ filled($user->nim_nip) ? $readonlyClass : $inputClass }}" />
                    </div>

                    <div>
                        <label for="profile-role" class="block text-label text-ink-soft">Peran</label>
                        <input id="profile-role" type="text"
                            value="{{ $user->isAdmin() ? 'Admin' : 'Pengguna' }}" readonly
                            class="mt-1.5 {{ $readonlyClass }}" />
                    </div>

                    <div>
                        <label for="profile-joined" class="block text-label text-ink-soft">Bergabung Sejak</label>
                        <input id="profile-joined" type="text" value="{{ $joinedLabel }}" readonly
                            class="mt-1.5 {{ $readonlyClass }}" />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="submit"
                            class="rounded-control bg-primary px-5 py-2.5 text-label text-white transition hover:bg-primary/90">
                            Simpan
                        </button>

                        <label for="profile-edit-toggle"
                            class="inline-flex cursor-pointer rounded-control border-[0.5px] border-line px-5 py-2.5 text-label text-ink transition hover:border-black/40">
                            Batal
                        </label>
                    </div>
                </form>
            </aside>

            <div class="min-w-0 flex-1">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <section class="rounded-control border-[0.5px] border-line bg-white p-5">
                        <p class="text-caption text-muted">Laporan</p>
                        <p class="mt-1 text-headline font-display text-ink">
                            {{ $stats['lostReports'] + $stats['foundReports'] }}
                        </p>
                        <p class="mt-1 text-body-sm text-muted">
                            {{ $stats['lostReports'] }} kehilangan · {{ $stats['foundReports'] }} penemuan
                        </p>
                    </section>

                    <section class="rounded-control border-[0.5px] border-line bg-white p-5">
                        <p class="text-caption text-muted">Klaim</p>
                        <p class="mt-1 text-headline font-display text-ink">{{ $stats['claims'] }}</p>
                        <p class="mt-1 text-body-sm text-muted">{{ $stats['pendingClaims'] }} menunggu verifikasi</p>
                    </section>

                    <section class="rounded-control border-[0.5px] border-line bg-white p-5">
                        <p class="text-caption text-muted">Serah Terima</p>
                        <p class="mt-1 text-headline font-display text-ink">
                            {{ $stats['handoversGiven'] + $stats['handoversReceived'] }}
                        </p>
                        <p class="mt-1 text-body-sm text-muted">
                            {{ $stats['handoversReceived'] }} diterima · {{ $stats['handoversGiven'] }} diserahkan
                        </p>
                    </section>
                </div>

                <section class="mt-8">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-title font-display">Laporan Terbaru</h2>

                        <a href="#" class="shrink-0 text-label text-primary transition hover:underline">Lihat semua</a>
                    </div>

                    <ul class="mt-4 divide-y divide-black/10 rounded-control border-[0.5px] border-line bg-white">
                        @forelse ($user->items as $item)
                            <li class="flex items-center gap-4 p-4">
                                <div class="size-12 shrink-0 overflow-hidden rounded-control bg-black/5">
                                    @if ($item->images->isNotEmpty())
                                        <img src="{{ asset('storage/' . $item->images->first()->path) }}"
                                            alt="{{ $item->title }}" class="h-full w-full object-cover" />
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-body font-semibold text-ink">{{ $item->title }}</p>
                                    <p class="truncate text-body-sm text-muted">
                                        {{ $item->category->name }} ·
                                        {{ $item->date_event->locale('id')->translatedFormat('j F Y') }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 flex-wrap items-center justify-end gap-1.5">
                                    <span @class([
                                        'inline-flex items-center rounded-control border-[0.5px] px-2.5 py-0.5 text-caption font-semibold',
                                        'bg-lost border-lost text-surface' => $item->type === 'lost',
                                        'bg-found border-found text-surface' => $item->type === 'found',
                                    ])>
                                        {{ $item->type === 'lost' ? 'Kehilangan' : 'Penemuan' }}
                                    </span>

                                    <span @class([
                                        'inline-flex items-center rounded-control border-[0.5px] px-2.5 py-0.5 text-caption font-semibold',
                                        'bg-open border-open text-surface' => $item->status === 'open',
                                        'bg-claimed border-claimed text-surface' => $item->status === 'claimed',
                                        'bg-returned border-returned text-surface' => $item->status === 'returned',
                                        'bg-closed border-closed text-surface' => $item->status === 'closed',
                                    ])>
                                        {{ match ($item->status) {
                                            'claimed' => 'Diklaim',
                                            'returned' => 'Dikembalikan',
                                            'closed' => 'Ditutup',
                                            default => 'Terbuka',
                                        } }}
                                    </span>
                                </div>
                            </li>
                        @empty
                            <li class="p-4 text-body-sm text-muted">
                                Belum ada laporan.
                                <a href="{{ route('lapor.kehilangan') }}" class="text-primary transition hover:underline">Buat
                                    laporan kehilangan</a>
                                atau
                                <a href="{{ route('lapor.penemuan') }}" class="text-primary transition hover:underline">laporan
                                    penemuan</a>.
                            </li>
                        @endforelse
                    </ul>
                </section>

                <section class="mt-8">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-title font-display">Klaim Terbaru</h2>

                        <a href="#" class="shrink-0 text-label text-primary transition hover:underline">Lihat semua</a>
                    </div>

                    <ul class="mt-4 divide-y divide-black/10 rounded-control border-[0.5px] border-line bg-white">
                        @forelse ($user->claims as $claim)
                            <li class="flex items-center gap-4 p-4">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-body font-semibold text-ink">
                                        {{ $claim->foundItem?->title ?? 'Barang temuan' }}
                                    </p>
                                    <p class="truncate text-body-sm text-muted">
                                        Diajukan
                                        {{ $claim->created_at->locale('id')->translatedFormat('j F Y') }}
                                        @if ($claim->admin_note)
                                            · {{ $claim->admin_note }}
                                        @endif
                                    </p>
                                </div>

                                <span @class([
                                    'inline-flex shrink-0 items-center rounded-control border-[0.5px] px-2.5 py-0.5 text-caption font-semibold',
                                    'bg-open border-open text-surface' => $claim->status === 'pending',
                                    'bg-returned border-returned text-surface' => $claim->status === 'approved',
                                    'bg-closed border-closed text-surface' => $claim->status === 'rejected',
                                ])>
                                    {{ match ($claim->status) {
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                        default => 'Menunggu',
                                    } }}
                                </span>
                            </li>
                        @empty
                            <li class="p-4 text-body-sm text-muted">Belum ada pengajuan klaim.</li>
                        @endforelse
                    </ul>
                </section>

                @if ($user->disputesReported->isNotEmpty())
                    <section class="mt-8">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-title font-display">Banding Terbaru</h2>

                            <a href="#" class="shrink-0 text-label text-primary transition hover:underline">Lihat semua</a>
                        </div>

                        <ul class="mt-4 divide-y divide-black/10 rounded-control border-[0.5px] border-line bg-white">
                            @foreach ($user->disputesReported as $dispute)
                                <li class="flex items-center gap-4 p-4">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-body font-semibold text-ink">
                                            {{ $dispute->claim?->foundItem?->title ?? 'Klaim' }}
                                        </p>
                                        <p class="truncate text-body-sm text-muted">
                                            Diajukan
                                            {{ $dispute->created_at->locale('id')->translatedFormat('j F Y') }}
                                        </p>
                                    </div>

                                    <span @class([
                                        'inline-flex shrink-0 items-center rounded-control border-[0.5px] px-2.5 py-0.5 text-caption font-semibold',
                                        'bg-open border-open text-surface' => $dispute->status === 'open',
                                        'bg-returned border-returned text-surface' => $dispute->status === 'resolved',
                                        'bg-closed border-closed text-surface' => $dispute->status === 'dismissed',
                                    ])>
                                        {{ match ($dispute->status) {
                                            'resolved' => 'Selesai',
                                            'dismissed' => 'Ditolak',
                                            default => 'Terbuka',
                                        } }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <section class="mt-8">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-title font-display">Notifikasi</h2>

                        <a href="{{ route('pengaturan') }}" class="shrink-0 text-label text-primary transition hover:underline">Atur</a>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 rounded-control border-[0.5px] border-line bg-white p-5 text-body-sm text-ink-soft">
                        <p>
                            Email:
                            <strong class="{{ $user->notify_email ? 'text-ink' : 'text-muted' }}">
                                {{ $user->notify_email ? 'Aktif' : 'Nonaktif' }}
                            </strong>
                        </p>
                        <p>
                            WhatsApp:
                            <strong class="{{ $user->notify_whatsapp ? 'text-ink' : 'text-muted' }}">
                                {{ $user->notify_whatsapp ? 'Aktif' : 'Nonaktif' }}
                            </strong>
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </main>
@endsection
