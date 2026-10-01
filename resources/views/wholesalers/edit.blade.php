<x-layouts.app title="Modifier un grossiste">
    <x-layout.page-header
        eyebrow="Commercial"
        title="Modifier {{ $wholesaler->name }}"
        description="La fiche grossiste conserve le site d'origine ; seul le rattachement client peut évoluer."
    />

    <div class="max-w-3xl">
        <x-wholesalers.form
            :wholesaler="$wholesaler"
            :action="route('wholesalers.update', $wholesaler)"
            method="PUT"
            submit-label="Enregistrer les modifications"
            :price-modes="$priceModes"
            :payment-conditions="$paymentConditions"
            :customer-options="$customerOptions"
        />
    </div>
</x-layouts.app>
