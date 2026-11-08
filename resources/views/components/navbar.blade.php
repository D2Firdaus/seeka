@php
    $type = is_string(request()->query('type')) ? request()->query('type') : '';
    $isKehilanganPage = request()->routeIs('lapor.kehilangan');

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

<header class="w-full">
    <div class="mx-auto w-full max-w-7xl px-5 sm:px-8">
        <div class="flex items-center justify-between gap-4 py-4">
            <a href="{{ route('home') }}" class="inline-block shrink-0" aria-label="{{ config('app.name', 'SEEKA') }} — beranda">
                <img src="{{ asset('images/seeka-mark.svg') }}" alt="{{ config('app.name', 'SEEKA') }}" class="-my-1.5 h-14 w-14" />
            </a>

            <div class="flex min-w-0 items-center gap-4 sm:gap-5">
                @auth
                    <a href="#" class="shrink-0 text-primary" aria-label="Notifikasi">
                        <x-icons.bell class="h-6 w-6" />
                    </a>
                    <a href="#" class="flex min-w-0 items-center gap-2 text-primary">
                        <x-icons.profile class="h-6 w-6 shrink-0" />
                        <span class="truncate text-body font-bold text-ink">{{ auth()->user()->name }}</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="shrink-0 rounded-control bg-primary px-5 py-2 text-label text-white transition hover:bg-primary/90">
                        Sign in
                    </a>
                @endauth
            </div>
        </div>
    </div>

    <div class="w-full border-b border-black/10">
        <div class="mx-auto w-full max-w-7xl px-5 sm:px-8">
            <nav class="flex items-stretch gap-x-5 text-body sm:gap-x-6">
                <a href="{{ route('home') }}" @class($tabClass($type === '' && ! $isKehilanganPage))>
                    Pencarian
                </a>

                <a href="{{ route('lapor.kehilangan') }}" @class([...$tabClass($type === 'lost' || $isKehilanganPage), 'hidden min-[300px]:flex'])>
                    Kehilangan
                </a>

                <a href="#" @class([...$tabClass($type === 'found'), 'hidden min-[380px]:flex'])>
                    Penemuan
                </a>

                <div class="flex items-stretch gap-x-5 min-[380px]:hidden">
                    <span class="my-3 w-px bg-black/15"></span>

                    <details class="relative">
                        <summary class="flex cursor-pointer list-none items-center gap-1.5 border-b-2 border-transparent py-3 text-ink-soft focus:outline-none focus-visible:outline-2 focus-visible:outline-primary">
                            Lainnya
                            <x-icons.dropdown class="h-2.5 w-2" />
                        </summary>

                        <div class="absolute right-0 z-30 mt-1 w-44 rounded-control border-[0.5px] border-line bg-white py-1.5 shadow-lg">
                            <a href="{{ route('lapor.kehilangan') }}" @class([...$menuItemClass($type === 'lost' || $isKehilanganPage), 'min-[300px]:hidden'])>
                                Kehilangan
                            </a>
                            <a href="#" @class([...$menuItemClass($type === 'found'), 'min-[380px]:hidden'])>
                                Penemuan
                            </a>
                        </div>
                    </details>
                </div>
            </nav>
        </div>
    </div>
</header>
