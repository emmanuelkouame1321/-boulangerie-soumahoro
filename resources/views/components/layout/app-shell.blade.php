{{-- Charte caisse : sidebar 256px + topbar 64px + contenu centré (max 1600px, padding 24px). --}}
<div {{ $attributes->merge(['class' => 'min-h-screen bg-canvas']) }}>
    <x-layout.sidebar />

    <div class="flex min-h-screen flex-col lg:pl-64">
        <x-layout.topbar />

        <main class="mx-auto w-full max-w-[1600px] flex-1 p-4 sm:p-6" id="contenu">
            {{ $slot }}
        </main>
    </div>
</div>
