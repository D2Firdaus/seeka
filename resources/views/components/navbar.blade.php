@php
    $type = is_string(request()->query('type')) ? request()->query('type') : '';
    $isKehilanganPage = request()->routeIs('lapor.kehilangan');
    $isPenemuanPage = request()->routeIs('lapor.penemuan');

    $dropdownMenuRoutes = [
        'profile.show' => 'Profil',
        'profile.update' => 'Profil',
        'laporan.saya' => 'Laporan Saya',
        'klaim.saya' => 'Klaim Saya',
        'pengaturan' => 'Pengaturan',
        'admin.dashboard' => 'Dashboard Admin',
        'admin.users' => 'Kelola Pengguna',
        'admin.reports' => 'Verifikasi Laporan',
        'admin.metadata' => 'Kelola Metadata',
    ];

    $currentDropdownTitle = null;
    foreach ($dropdownMenuRoutes as $routePattern => $title) {
        if (request()->routeIs($routePattern)) {
            $currentDropdownTitle = $title;
            break;
        }
    }

    $isSimpleNav = $currentDropdownTitle !== null;

    $tabClass = fn (bool $active) => [
        'items-center border-b-2 py-3 text-ink-soft',
        'border-primary' => $active,
        'border-transparent' => ! $active,
    ];

    $menuItemClass = fn (bool $active) => [
        'block px-4 py-2 text-body hover:bg-black/5',
        'font-semibold text-primary' => $active,
    ];
@endphp

<header @class([
    'w-full',
    'border-b border-black/10' => $isSimpleNav,
])>
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-8">
        <div class="flex items-center justify-between gap-4 py-4">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('home') }}" class="inline-block shrink-0" aria-label="{{ config('app.name', 'SEEKA') }} — beranda">
                    <img src="{{ asset('images/seeka-mark.svg') }}" alt="{{ config('app.name', 'SEEKA') }}" class="-my-1.5 h-14 w-14" />
                </a>
                @if ($isSimpleNav)
                    <span class="truncate text-title font-semibold text-ink">{{ $currentDropdownTitle }}</span>
                @endif
            </div>

            <div class="flex min-w-0 items-center gap-4 sm:gap-5">
                @auth
                    <a href="#" class="shrink-0 text-primary" aria-label="Notifikasi">
                        <x-icons.bell class="h-6 w-6" />
                    </a>
                    <details class="group relative" data-animated-dropdown>
                        <summary class="flex cursor-pointer list-none items-center gap-2 text-primary focus:outline-none focus-visible:outline-2 focus-visible:outline-primary">
                            <x-icons.profile class="h-6 w-6 shrink-0" />
                            <span class="truncate text-body font-bold text-ink">{{ auth()->user()->name }}</span>
                            <x-icons.dropdown class="h-2.5 w-2 shrink-0 transition-transform group-open:rotate-180" />
                        </summary>

                        <div data-dropdown-menu class="dropdown-menu-open absolute right-0 z-30 mt-2 w-60 rounded-control border-[0.5px] border-line bg-white shadow-lg">
                            <div class="border-b border-black/10 px-4 py-3">
                                <p class="text-body font-semibold text-ink">{{ auth()->user()->name }}</p>
                                <p class="truncate text-caption text-muted">{{ auth()->user()->email }}</p>
                            </div>

                            <div class="py-1.5">
                                <a href="{{ route('profile.show') }}" class="flex items-center gap-3 px-4 py-2 text-body hover:bg-black/5">
                                    Profil
                                </a>
                                <a href="#" class="flex items-center gap-3 px-4 py-2 text-body hover:bg-black/5">
                                    Laporan Saya
                                </a>
                                <a href="#" class="flex items-center gap-3 px-4 py-2 text-body hover:bg-black/5">
                                    Klaim Saya
                                </a>
                            </div>

                            @if (auth()->user()->isAdmin())
                                <div class="border-t border-black/10 py-1.5">
                                    <a href="#" class="flex items-center gap-3 px-4 py-2 text-body hover:bg-black/5">
                                        Dashboard Admin
                                    </a>
                                    <a href="#" class="flex items-center gap-3 px-4 py-2 text-body hover:bg-black/5">
                                        Kelola Pengguna
                                    </a>
                                    <a href="#" class="flex items-center gap-3 px-4 py-2 text-body hover:bg-black/5">
                                        Verifikasi Laporan
                                    </a>
                                    <a href="#" class="flex items-center gap-3 px-4 py-2 text-body hover:bg-black/5">
                                        Kelola Metadata
                                    </a>
                                </div>
                            @endif

                            <div class="border-t border-black/10 py-1.5">
                                <a href="#" class="flex items-center gap-3 px-4 py-2 text-body hover:bg-black/5">
                                    Pengaturan
                                </a>
                            </div>

                            <div class="border-t border-black/10 py-1.5">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-3 px-4 py-2 text-left text-body hover:bg-black/5">
                                        Keluar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </details>
                @else
                    <a href="{{ route('login') }}" class="shrink-0 rounded-control bg-primary px-5 py-2 text-label text-white transition hover:bg-primary/90">
                        Sign in
                    </a>
                @endauth
            </div>
        </div>
    </div>

    @unless ($isSimpleNav)
        <div class="w-full border-b border-black/10">
            <div class="mx-auto w-full max-w-7xl px-5 sm:px-8">
                <nav class="flex items-stretch gap-x-5 text-body sm:gap-x-6">
                    <a href="{{ route('home') }}" @class($tabClass($type === '' && ! $isKehilanganPage && ! $isPenemuanPage))>
                        Pencarian
                    </a>

                    <a href="{{ route('lapor.kehilangan') }}" @class([...$tabClass($type === 'lost' || $isKehilanganPage), 'hidden min-[300px]:flex'])>
                        Kehilangan
                    </a>

                    <a href="{{ route('lapor.penemuan') }}" @class([...$tabClass($type === 'found' || $isPenemuanPage), 'hidden min-[380px]:flex'])>
                        Penemuan
                    </a>

                    <div class="flex items-stretch gap-x-5 min-[380px]:hidden">
                        <span class="my-3 w-px bg-black/15"></span>

                        <details class="relative" data-animated-dropdown>
                            <summary class="flex cursor-pointer list-none items-center gap-1.5 border-b-2 border-transparent py-3 text-ink-soft focus:outline-none focus-visible:outline-2 focus-visible:outline-primary">
                                Lainnya
                                <x-icons.dropdown class="h-2.5 w-2" />
                            </summary>

                            <div data-dropdown-menu class="dropdown-menu-open absolute right-0 z-30 mt-1 w-44 rounded-control border-[0.5px] border-line bg-white py-1.5 shadow-lg">
                                <a href="{{ route('lapor.kehilangan') }}" @class([...$menuItemClass($type === 'lost' || $isKehilanganPage), 'min-[300px]:hidden'])>
                                    Kehilangan
                                </a>
                                <a href="{{ route('lapor.penemuan') }}" @class([...$menuItemClass($type === 'found' || $isPenemuanPage), 'min-[380px]:hidden'])>
                                    Penemuan
                                </a>
                            </div>
                        </details>
                    </div>
                </nav>
            </div>
        </div>
    @endunless
</header>
