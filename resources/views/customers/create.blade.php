<x-layouts.app title="Nouveau client">
    <x-layout.page-header
        eyebrow="Commercial"
        title="Nouveau client"
        description="Un client appartient au site courant ; la vente au comptoir peut se faire sans client identifié."
    />

    <div class="max-w-3xl">
        <x-customers.form
            :customer="null"
            :action="route('customers.store')"
            submit-label="Créer le client"
        />
    </div>
</x-layouts.app>
