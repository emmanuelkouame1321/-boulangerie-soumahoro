@props([
    'title' => 'Caisse',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} — {{ config('app.name') }}</title>

    {{-- Le logo du site fait office de favicon (ADR-032). --}}
    <link rel="icon" href="{{ \App\Support\Branding::faviconUrl() }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=roboto:400,500,700,900" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full overflow-hidden bg-canvas text-ink">
    {{ $slot }}

    <x-ui.toast />

    @livewireScripts
</body>
</html>
