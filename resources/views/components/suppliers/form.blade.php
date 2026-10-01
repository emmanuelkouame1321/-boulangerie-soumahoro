@props([
    'supplier',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Enregistrer',
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Identité</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.form-field name="name" label="Raison sociale" :value="$supplier->name" :autofocus="true" required />
            <x-ui.form-field name="contact_name" label="Nom du contact" :value="$supplier->contact_name" />
            <x-ui.form-field name="phone" label="Téléphone" inputmode="tel" :value="$supplier->phone" />
            <x-ui.form-field name="email" label="Email" type="email" :value="$supplier->email" />
            <x-ui.form-field name="identification" label="Numéro d’identification" :value="$supplier->identification" help="RCCM, compte contribuable…" />
            <x-ui.form-field name="categories" label="Catégories fournies" :value="$supplier->categories" help="Ex. farine, levure, emballages." />
        </div>

        <div class="mt-4">
            <x-ui.form-field name="address" label="Adresse" :value="$supplier->address" />
        </div>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Conditions commerciales</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.form-field name="payment_terms" label="Conditions de paiement" :value="$supplier->payment_terms" placeholder="Comptant, 30 jours…" />
            <x-ui.form-field
                name="delivery_delay_days"
                label="Délai moyen de livraison (jours)"
                type="number"
                inputmode="numeric"
                :value="$supplier->delivery_delay_days"
            />
        </div>

        <div class="mt-4">
            <label for="field-notes" class="mb-1.5 block text-xs font-bold text-ink">Observations</label>

            <textarea
                id="field-notes"
                name="notes"
                rows="3"
                class="block w-full rounded-control border bg-surface px-3 py-2.5 text-sm text-ink transition
                    placeholder:text-soft focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none
                    {{ $errors->has('notes') ? 'border-danger' : 'border-line-strong' }}"
                @if ($errors->has('notes')) aria-invalid="true" aria-describedby="field-notes-error" @endif
            >{{ old('notes', $supplier->notes) }}</textarea>

            @error('notes')
                <p id="field-notes-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <label class="mt-4 flex items-center gap-2.5 text-sm">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked(old('is_active', $supplier->is_active ?? true))
                class="size-4 rounded-control border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-100"
            >
            Fournisseur actif
        </label>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('suppliers.index')">Annuler</x-ui.button>
        <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
    </div>
</form>
