<x-layouts.app title="Modifier le produit">
    <x-layout.page-header
        eyebrow="Opérations"
        title="Modifier le produit"
        description="{{ $product->name }} — {{ $product->sku }}"
    />

    <div class="max-w-4xl">
        <x-products.form
            :product="$product"
            :action="route('products.update', $product)"
            method="PUT"
            :type-options="$typeOptions"
            :unit-options="$unitOptions"
            :category-options="$categoryOptions"
            :alert-mode-options="$alertModeOptions"
            submit-label="Enregistrer les modifications"
        />
    </div>
</x-layouts.app>
