<x-layouts.app title="Nouvelle charge">
    <x-layout.page-header
        eyebrow="Dépenses d'exploitation"
        title="Nouvelle charge"
        description="Une charge appartient au site courant ; sa référence est générée côté serveur."
    />

    <div class="max-w-3xl">
        <x-expenses.form
            :expense="null"
            :action="route('expenses.store')"
            submit-label="Enregistrer la charge"
            :category-options="$categoryOptions"
            :payment-methods="$paymentMethods"
        />
    </div>
</x-layouts.app>
