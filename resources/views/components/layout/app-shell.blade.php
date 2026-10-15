{{-- Charte caisse : sidebar 256px + topbar 64px + contenu centré (max 1600px, padding 24px).

     Alpine n'initialise que les sous-arbres dont la racine porte `x-data` (ou
     un composant Livewire `wire:id`). La coquille est donc déclarée comme
     racine Alpine : sans elle le bouton burger de la topbar — qui n'est
     enfermé dans aucun `x-data` — n'était jamais lié et le menu mobile ne
     s'ouvrait pas. --}}
<div {{ $attributes->merge(['class' => 'min-h-screen bg-canvas']) }} x-data>
    <x-layout.sidebar />

    <div class="flex min-h-screen flex-col lg:pl-64">
        <x-layout.topbar />

        <main class="mx-auto w-full max-w-[1600px] flex-1 p-4 sm:p-6" id="contenu">
            {{ $slot }}
        </main>
    </div>

    {{-- Palette de recherche globale : montée ici — et non dans la topbar —
         pour que son panneau fixe échappe au contexte d'empilement de
         l'en-tête collant. --}}
    <livewire:search.global-search />
</div>
