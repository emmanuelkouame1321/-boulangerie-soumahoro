@props([
    'siteName' => 'Toutes les boulangeries',
    'siteMeta' => null,
])

{{-- Design System §9.1 : sidebar 248px + topbar 64px + zone contenu
     max-width 1600px, centrée, padding 24px. --}}
<div {{ $attributes->merge(['class' => 'min-h-screen bg-canvas']) }}>
    <x-layout.sidebar />

    <div class="flex min-h-screen flex-col lg:pl-[248px]">
        <x-layout.topbar :site-name="$siteName" :site-meta="$siteMeta" />

        <main class="mx-auto w-full max-w-[1600px] flex-1 p-4 sm:p-6" id="contenu">
            {{ $slot }}
        </main>
    </div>
</div>
