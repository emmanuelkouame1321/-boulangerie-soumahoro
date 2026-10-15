<x-layouts.app title="Nouveau produit">
    <x-layout.page-header
        eyebrow="Opérations"
        title="Nouveau produit"
        description="Créez une matière première ou un produit fini."
    />

    <div class="max-w-4xl">
        <x-products.form
            :product="$product"
            :action="route('products.store')"
            :group-options="$groupOptions"
            :types-by-group="$typesByGroup"
            :unit-options="$unitOptions"
            :category-options="$categoryOptions"
            :alert-mode-options="$alertModeOptions"
            submit-label="Créer le produit"
        />
    </div>
</x-layouts.app>
