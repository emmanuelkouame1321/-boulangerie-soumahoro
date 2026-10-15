<x-layouts.app title="Nouvelle recette">
    <x-layout.page-header
        eyebrow="Production"
        title="Nouvelle recette"
        description="Produit fini, rendement par lot, durées et ressources avec leur mode de déduction."
    >
        <x-slot:actions>
            <x-ui.button variant="ghost" icon="arrow-left" :href="route('production.recipes.index')">Retour</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if ($sourceRecipe !== null)
        <x-ui.alert tone="info" class="mb-4">
            Recette initialisée depuis « {{ $sourceRecipe->name }} ». Ajustez le nom et les ressources : la référence reste à définir pour ne rien écraser.
        </x-ui.alert>
    @endif

    <x-production.recipe-form
        :recipe="$recipe"
        :item-rows="$itemRows"
        :product-options="$productOptions"
        :item-product-options="$itemProductOptions"
        :unit-options="$unitOptions"
        :mode-options="$modeOptions"
        :action="route('production.recipes.store')"
        submit-label="Créer la recette"
    />
</x-layouts.app>
