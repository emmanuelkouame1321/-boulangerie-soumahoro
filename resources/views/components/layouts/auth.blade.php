@props([
    'title' => null,
])

{{-- Charte caisse : carte centrée, marque BakeryOS au-dessus du formulaire. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=roboto:400,500,700,900" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-canvas text-ink">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <div class="mx-auto grid size-12 place-items-center rounded-card bg-brand-700 text-base font-extrabold text-white">
                    B
                </div>

                <p class="mt-3 text-sm font-bold tracking-[0.18em] text-brand-800 uppercase">
                    Boulangerie Soumahoro
                </p>
            </div>

            <div class="rounded-modal border border-line bg-surface p-6 shadow-card sm:p-8">
                {{ $slot }}
            </div>

            <p class="mt-6 text-center text-xs text-muted">
                Produire juste. Vendre mieux. Piloter simplement.
            </p>
        </div>
    </main>

    @livewireScripts
</body>
</html>
