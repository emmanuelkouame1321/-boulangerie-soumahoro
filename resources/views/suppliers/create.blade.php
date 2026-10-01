<x-layouts.app title="Nouveau fournisseur">
    <x-layout.page-header
        eyebrow="Achats"
        title="Nouveau fournisseur"
        description="Un fournisseur peut alimenter plusieurs produits ; le prix négocié se règle produit par produit."
    />

    <div class="max-w-4xl">
        <x-suppliers.form
            :supplier="$supplier"
            :action="route('suppliers.store')"
            submit-label="Créer le fournisseur"
        />
    </div>
</x-layouts.app>
