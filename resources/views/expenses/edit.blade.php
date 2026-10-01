<x-layouts.app title="Modifier une charge">
    <x-layout.page-header
        eyebrow="Dépenses d'exploitation"
        title="Modifier {{ $expense->reference }}"
        description="La charge conserve son site et sa référence d'origine."
    />

    <div class="max-w-3xl">
        <x-expenses.form
            :expense="$expense"
            :action="route('expenses.update', $expense)"
            method="PUT"
            submit-label="Enregistrer les modifications"
            :category-options="$categoryOptions"
            :payment-methods="$paymentMethods"
        />
    </div>
</x-layouts.app>
