<x-layouts.app title="Modifier un client">
    <x-layout.page-header
        eyebrow="Commercial"
        title="{{ $customer->name }}"
        description="Mettre à jour la fiche du client."
    />

    <div class="max-w-3xl">
        <x-customers.form
            :customer="$customer"
            :action="route('customers.update', $customer)"
            method="PUT"
            submit-label="Enregistrer les modifications"
        />
    </div>
</x-layouts.app>
