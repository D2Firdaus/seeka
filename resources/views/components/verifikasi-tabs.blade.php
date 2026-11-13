@props(['active'])

@php
    $tabClass = fn (bool $isActive) => [
        'items-center border-b-2 py-3 text-ink-soft',
        'border-primary' => $isActive,
        'border-transparent' => ! $isActive,
    ];
@endphp

<nav {{ $attributes->merge(['class' => 'mt-2 mb-5 flex items-stretch gap-x-5 border-b border-black/10 text-body sm:gap-x-6']) }}>
    <a href="{{ route('admin.verifikasi.klaim') }}" @class($tabClass($active === 'klaim'))>
        Verifikasi Klaim
    </a>

    <a href="{{ route('admin.verifikasi.banding') }}" @class($tabClass($active === 'banding'))>
        Verifikasi Banding
    </a>
</nav>
