<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', config('app.name', 'SEEKA'))</title>

    @fonts

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen flex-col bg-surface text-ink antialiased">
    <header class="w-full">
        <div class="mx-auto w-full max-w-6xl px-5 pt-6 sm:px-8 sm:pt-8">
            <div class="mx-auto max-w-[440px]">
                <a href="{{ route('home') }}" class="inline-block" aria-label="{{ config('app.name', 'SEEKA') }} — beranda">
                    <img src="{{ asset('images/seeka-logo.svg') }}" alt="{{ config('app.name', 'SEEKA') }}" class="-my-4 -ml-1.5 h-20 w-auto sm:-ml-2 sm:h-24" />
                </a>
            </div>
        </div>
    </header>

    @yield('content')

    <footer class="w-full">
        <div class="mx-auto w-full max-w-6xl px-5 py-6 sm:px-8">
            <div class="mx-auto max-w-[440px] text-caption text-subtle">
                &copy; {{ date('Y') }} {{ config('app.name', 'SEEKA') }}
            </div>
        </div>
    </footer>
</body>

</html>

