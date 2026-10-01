@props([
    'expense' => null,
    'action',
    'method' => 'POST',
    'submitLabel' => 'Enregistrer',
    'categoryOptions' => [],
    'paymentMethods' => [],
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Charge</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.select
                name="expense_category_id"
                label="Catégorie"
                :options="$categoryOptions"
                :selected="$expense?->expense_category_id"
                placeholder="— non classé —"
            />

            <x-ui.form-field name="label" label="Objet" :value="$expense?->label" placeholder="Ex. Facture électricité" />

            <x-ui.form-field
                name="amount"
                label="Montant (FCFA)"
                type="number"
                min="0"
                step="1"
                :value="$expense?->amount ?? 0"
                required
            />

            <x-ui.form-field
                name="spent_on"
                label="Date"
                type="date"
                :value="$expense?->spent_on?->format('Y-m-d') ?? now()->format('Y-m-d')"
                required
            />

            <x-ui.form-field name="payee" label="Bénéficiaire / fournisseur" :value="$expense?->payee" />

            <x-ui.select
                name="method"
                label="Mode de paiement"
                :options="$paymentMethods"
                :selected="$expense?->method ?? 'cash'"
            />
        </div>

        <div class="mt-4">
            <label for="field-comment" class="mb-1.5 block text-xs font-bold text-ink">Commentaire</label>

            <textarea
                id="field-comment"
                name="comment"
                rows="2"
                class="block w-full rounded-control border bg-surface px-3 py-2.5 text-sm text-ink transition
                    placeholder:text-soft focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none
                    {{ $errors->has('comment') ? 'border-danger' : 'border-line-strong' }}"
            >{{ old('comment', $expense?->comment) }}</textarea>

            @error('comment')
                <p class="mt-1.5 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('expenses.index')">Annuler</x-ui.button>
        <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
    </div>
</form>
