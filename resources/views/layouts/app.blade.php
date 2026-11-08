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
    <x-navbar />

    @if (session('success'))
        <div class="mx-auto w-full max-w-6xl px-5 pt-6 sm:px-8">
            <x-alert type="success">{{ session('success') }}</x-alert>
        </div>
    @endif

    @yield('content')
</body>

</html>
