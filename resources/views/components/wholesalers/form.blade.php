@props([
    'wholesaler' => null,
    'action',
    'method' => 'POST',
    'submitLabel' => 'Enregistrer',
    'priceModes' => [],
    'paymentConditions' => [],
    'customerOptions' => [],
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Identité</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.form-field name="name" label="Nom / raison sociale" :value="$wholesaler?->name" :autofocus="true" required />
            <x-ui.form-field name="contact_name" label="Personne responsable" :value="$wholesaler?->contact_name" />
            <x-ui.form-field name="phone" label="Téléphone" inputmode="tel" :value="$wholesaler?->phone" />
            <x-ui.form-field name="client_type" label="Type de client" :value="$wholesaler?->client_type" placeholder="Grossiste, hôtel, restaurant…" />
            <x-ui.form-field name="address" label="Adresse" :value="$wholesaler?->address" />
            <x-ui.form-field name="zone" label="Zone" :value="$wholesaler?->zone" placeholder="Centre, Cocody…" />
        </div>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Conditions commerciales</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.select
                name="price_mode"
                label="Grille de prix"
                :options="$priceModes"
                :selected="$wholesaler?->price_mode ?? 'wholesale'"
                required
            />

            <x-ui.form-field
                name="usual_quantity"
                label="Quantité habituelle par jour"
                type="number"
                step="0.0001"
                min="0"
                :value="$wholesaler?->usual_quantity ?? '0'"
                required
            />

            <x-ui.select
                name="payment_condition"
                label="Conditions de paiement"
                :options="$paymentConditions"
                :selected="$wholesaler?->payment_condition"
                placeholder="—"
            />

            <x-ui.select
                name="customer_id"
                label="Compte client lié"
                :options="$customerOptions"
                :selected="$wholesaler?->customer_id"
                placeholder="— aucun —"
                help="Le compte client relie la fiche à l'historique de ventes et au calcul de ristourne."
            />

            <x-ui.form-field name="joined_on" label="Date d'adhésion" type="date" :value="$wholesaler?->joined_on?->format('Y-m-d')" />
        </div>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Observations</h2>

        <div class="mt-4">
            <label for="field-note" class="mb-1.5 block text-xs font-bold text-ink">Observations</label>

            <textarea
                id="field-note"
                name="note"
                rows="3"
                class="block w-full rounded-control border bg-surface px-3 py-2.5 text-sm text-ink transition
                    placeholder:text-soft focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none
                    {{ $errors->has('note') ? 'border-danger' : 'border-line-strong' }}"
                @if ($errors->has('note')) aria-invalid="true" aria-describedby="field-note-error" @endif
            >{{ old('note', $wholesaler?->note) }}</textarea>

            @error('note')
                <p id="field-note-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>

        <input type="hidden" name="is_active" value="0">
        <label class="mt-4 flex items-center gap-2.5 text-sm">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked(old('is_active', $wholesaler?->is_active ?? true))
                class="size-4 rounded-control border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-100"
            >
            Grossiste actif
        </label>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('wholesalers.index')">Annuler</x-ui.button>
        <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
    </div>
</form>
