<x-layouts.app title="Modifier le fournisseur">
    <x-layout.page-header
        eyebrow="Achats"
        title="Modifier le fournisseur"
        description="{{ $supplier->name }}"
    />

    <div class="max-w-4xl">
        <x-suppliers.form
            :supplier="$supplier"
            :action="route('suppliers.update', $supplier)"
            method="PUT"
            submit-label="Enregistrer les modifications"
        />
    </div>
</x-layouts.app>
