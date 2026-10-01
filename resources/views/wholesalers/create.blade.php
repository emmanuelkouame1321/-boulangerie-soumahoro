<x-layouts.app title="Nouveau grossiste">
    <x-layout.page-header
        eyebrow="Commercial"
        title="Nouveau grossiste"
        description="Une fiche grossiste appartient au site courant. Reliez-la à un compte client pour activer le calcul de ristourne."
    />

    <div class="max-w-3xl">
        <x-wholesalers.form
            :wholesaler="null"
            :action="route('wholesalers.store')"
            submit-label="Créer le grossiste"
            :price-modes="$priceModes"
            :payment-conditions="$paymentConditions"
            :customer-options="$customerOptions"
        />
    </div>
</x-layouts.app>
