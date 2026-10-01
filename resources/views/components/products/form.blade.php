@props([
    'product',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Enregistrer',
    'typeOptions' => [],
    'unitOptions' => [],
    'categoryOptions' => [],
    'alertModeOptions' => [],
])

@php
    // Le seuil est un decimal ; on retire les zéros inutiles pour la saisie.
    $threshold = $product->alert_threshold;
    $threshold = $threshold === null ? null : rtrim(rtrim((string) $threshold, '0'), '.');
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Identité</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.form-field name="name" label="Nom" :value="$product->name" :autofocus="true" required />
            <x-ui.form-field name="sku" label="Code produit (SKU)" :value="$product->sku" help="Identifiant unique et stable." required />

            <x-ui.select name="type" label="Type" :options="$typeOptions" :selected="$product->type->value" required />
            <x-ui.select
                name="product_category_id"
                label="Catégorie"
                :options="$categoryOptions"
                :selected="$product->product_category_id"
                placeholder="— Non classé —"
            />
        </div>

        <div class="mt-4">
            <label for="field-description" class="mb-1.5 block text-xs font-bold text-ink">Description</label>

            <textarea
                id="field-description"
                name="description"
                rows="3"
                class="block w-full rounded-control border bg-surface px-3 py-2.5 text-sm text-ink transition
                    placeholder:text-soft focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none
                    {{ $errors->has('description') ? 'border-danger' : 'border-line-strong' }}"
                @if ($errors->has('description')) aria-invalid="true" aria-describedby="field-description-error" @endif
            >{{ old('description', $product->description) }}</textarea>

            @error('description')
                <p id="field-description-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Unités, seuils et durée de vente</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.select name="primary_unit_id" label="Unité principale" :options="$unitOptions" :selected="$product->primary_unit_id" required />
            <x-ui.select
                name="secondary_unit_id"
                label="Unité secondaire"
                :options="$unitOptions"
                :selected="$product->secondary_unit_id"
                placeholder="— Aucune —"
                help="Conversion gérée dans les unités (ex. 1 sac = 50 kg)."
            />

            <x-ui.form-field
                name="alert_threshold"
                label="Seuil d’alerte"
                type="number"
                step="any"
                inputmode="decimal"
                :value="$threshold"
                help="Quantité minimale avant alerte."
            />

            <x-ui.select name="alert_mode" label="Mode d’alerte" :options="$alertModeOptions" :selected="$product->alert_mode" required />

            <x-ui.form-field
                name="shelf_life_days"
                label="Durée limite de vente (jours)"
                type="number"
                inputmode="numeric"
                :value="$product->shelf_life_days"
                help="Laisser vide si sans objet."
            />
        </div>

        <label class="mt-4 flex items-center gap-2.5 text-sm">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked(old('is_active', $product->is_active ?? true))
                class="size-4 rounded-control border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-100"
            >
            Produit actif
        </label>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Prix (FCFA)</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-ui.form-field name="purchase_price" label="Prix d’achat" type="number" inputmode="numeric" :value="$product->purchase_price ?? 0" required />
            <x-ui.form-field name="sale_price" label="Prix de vente" type="number" inputmode="numeric" :value="$product->sale_price ?? 0" required />
            <x-ui.form-field name="wholesale_price" label="Prix grossiste" type="number" inputmode="numeric" :value="$product->wholesale_price ?? 0" required />
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('products.index')">Annuler</x-ui.button>
        <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
    </div>
</form>
