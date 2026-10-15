@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Recettes de production">
    <x-layout.page-header
        eyebrow="Production"
        title="Recettes & nomenclatures"
        description="Les recettes configurables : ressources nécessaires, modes de déduction et rendement théorique par lot."
    >
        <x-slot:actions>
            <x-ui.button variant="ghost" icon="arrow-left" :href="route('production.index')">Retour</x-ui.button>
            @if ($canManage)
                <x-ui.button variant="primary" icon="plus" :href="route('production.recipes.create')">Nouvelle recette</x-ui.button>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card flush class="mb-4">
        <form method="GET" action="{{ route('production.recipes.index') }}" class="flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-ui.search-input name="search" :value="$search" placeholder="Nom ou référence de recette…" />
            </div>
            <x-ui.button type="submit" variant="secondary">Rechercher</x-ui.button>
            @if ($search !== '')
                <x-ui.button variant="ghost" :href="route('production.recipes.index')">Effacer</x-ui.button>
            @endif
        </form>
    </x-ui.card>

    @if ($recipes->isEmpty())
        <x-ui.empty-state
            icon="clipboard-list"
            title="{{ $search !== '' ? 'Aucun résultat' : 'Aucune recette' }}"
            description="Les recettes définissent les matières et le rendement d’une production."
        />
    @else
        <div class="space-y-4">
            @foreach ($recipes as $recipe)
                <x-business.recipe-preview :recipe="$recipe" :batches="1">
                    @if ($canManage)
                        <x-slot:actions>
                            <x-ui.button
                                variant="ghost"
                                size="sm"
                                icon="pen"
                                :href="route('production.recipes.edit', $recipe)"
                            >Modifier</x-ui.button>
                            <x-ui.button
                                variant="ghost"
                                size="sm"
                                icon="copy"
                                :href="route('production.recipes.create', ['from' => $recipe->id])"
                            >Dupliquer</x-ui.button>
                        </x-slot:actions>
                    @endif
                </x-business.recipe-preview>
            @endforeach

            <div>
                {{ $recipes->links() }}
            </div>
        </div>
    @endif
</x-layouts.app>
