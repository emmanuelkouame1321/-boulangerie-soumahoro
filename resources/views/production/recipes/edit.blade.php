<x-layouts.app title="Modifier la recette">
    <x-layout.page-header
        eyebrow="Production"
        title="Modifier la recette"
        description="Ajustez la configuration et les ressources. Les productions déjà validées ne sont pas modifiées."
    >
        <x-slot:actions>
            <x-ui.button variant="ghost" icon="arrow-left" :href="route('production.recipes.index')">Retour</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-production.recipe-form
        :recipe="$recipe"
        :item-rows="$itemRows"
        :product-options="$productOptions"
        :item-product-options="$itemProductOptions"
        :unit-options="$unitOptions"
        :mode-options="$modeOptions"
        :action="route('production.recipes.update', $recipe)"
        method="PUT"
        submit-label="Enregistrer les modifications"
    />
</x-layouts.app>
