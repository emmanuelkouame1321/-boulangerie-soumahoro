@props([
    'recipe',
    'itemRows',
    'productOptions' => [],
    'itemProductOptions' => [],
    'unitOptions' => [],
    'modeOptions' => [],
    'action',
    'method' => 'POST',
    'submitLabel' => 'Enregistrer',
])

@php
    use Illuminate\Support\Js;

    // Lignes de ressources : repartir des saisies précédentes en cas
    // d'erreur de validation, sinon du modèle (vierge, existant ou dupliqué).
    $initialItems = old('items', $itemRows);
    $initialItems = is_array($initialItems) && $initialItems !== [] ? array_values($initialItems) : $itemRows;
@endphp

@if ($errors->any())
    <x-ui.alert tone="danger" class="mb-4">
        Certaines informations sont incomplètes. Vérifiez le produit fini et les ressources de la recette.
    </x-ui.alert>
@endif

<form
    method="POST"
    action="{{ $action }}"
    class="space-y-6"
    x-data="{
        items: {{ Js::from($initialItems) }},
        productOptions: {{ Js::from($itemProductOptions) }},
        unitOptions: {{ Js::from($unitOptions) }},
        modeOptions: {{ Js::from($modeOptions) }},
        addItem() {
            this.items.push({ product_id: '', unit_id: '', quantity: '1', deduction_mode: 'quantity', unit_cost: '' });
        },
        removeItem(index) {
            if (this.items.length > 1) { this.items.splice(index, 1); }
        },
        isCostBased(mode) {
            return mode === 'monetary' || mode === 'cumulated';
        }
    }"
>
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <h2 class="text-sm font-bold text-ink">Configuration de la recette</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-ui.select
                name="product_id"
                label="Produit fini"
                :options="$productOptions"
                :selected="$recipe->product_id"
                placeholder="— Choisir —"
                help="Le produit fabriqué par cette recette."
                required
            />

            <x-ui.form-field
                name="reference"
                label="Référence"
                :value="$recipe->reference"
                placeholder="RCP-…"
                help="Facultatif, unique."
            />

            <x-ui.form-field name="name" label="Nom de la recette" :value="$recipe->name" required />

            <x-ui.form-field
                name="yield_quantity"
                label="Rendement par lot"
                type="number"
                step="any"
                inputmode="decimal"
                :value="$recipe->yield_quantity"
                help="Quantité de produit fini attendue par lot."
                required
            />

            <x-ui.form-field
                name="production_time_minutes"
                label="Durée de production (minutes)"
                type="number"
                inputmode="numeric"
                :value="$recipe->production_time_minutes"
                help="Laisser vide si sans objet."
            />

            <x-ui.form-field
                name="shelf_life_days"
                label="Durée limite de vente (jours)"
                type="number"
                inputmode="numeric"
                :value="$recipe->shelf_life_days"
                help="Laisser vide si sans objet."
            />

            <x-ui.form-field
                name="allowed_loss_percent"
                label="Pertes autorisées (%)"
                type="number"
                step="any"
                inputmode="decimal"
                :value="$recipe->allowed_loss_percent"
                help="Pourcentage de perte toléré par lot."
            />
        </div>

        <label class="mt-4 flex items-center gap-2.5 text-sm">
            <input type="hidden" name="is_active" value="0">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                @checked(old('is_active', $recipe->is_active ?? true))
                class="size-4 rounded-control border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-100"
            >
            Recette active
        </label>

        <div class="mt-4">
            <label for="field-note" class="mb-1.5 block text-xs font-bold text-ink">Observation</label>

            <textarea
                id="field-note"
                name="note"
                rows="3"
                placeholder="Facultatif."
                class="block w-full rounded-control border bg-surface px-3 py-2.5 text-sm text-ink transition
                    placeholder:text-soft focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none
                    {{ $errors->has('note') ? 'border-danger' : 'border-line-strong' }}"
                @if ($errors->has('note')) aria-invalid="true" aria-describedby="field-note-error" @endif
            >{{ old('note', $recipe->note) }}</textarea>

            @error('note')
                <p id="field-note-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-ink">Ressources par lot</h2>
                <p class="text-[13px] text-muted">Matières premières, ressources monétaires ou cumulées nécessaires à un lot.</p>
            </div>
            <x-ui.button type="button" variant="secondary" size="sm" icon="plus" @click="addItem()">Ajouter une ressource</x-ui.button>
        </div>

        @error('items')
            <p class="mt-2 text-sm text-danger">{{ $message }}</p>
        @enderror

        <div class="mt-4 space-y-3">
            <template x-for="(item, index) in items" :key="index">
                <div class="rounded-card border border-line bg-canvas p-3 sm:p-4">
                    {{-- Ligne de ressource. La disposition compacte sur une
                         seule ligne n'apparaît qu'en grand écran : à 1024 px la
                         barre latérale laisse trop peu de place aux colonnes
                         fixes (relevé ADR-029 §89, dépassement de 17 px). --}}
                    <div class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-[1fr_7rem_8rem_11rem_9rem_auto]">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-ink" :for="'item-product-'+index">Ressource</label>
                            <select
                                :id="'item-product-'+index"
                                :name="'items['+index+'][product_id]'"
                                x-model="item.product_id"
                                required
                                class="block w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                            >
                                <option value="">— Choisir —</option>
                                <template x-for="(label, id) in productOptions" :key="id">
                                    <option :value="id" x-text="label"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-ink" :for="'item-quantity-'+index">Quantité</label>
                            <input
                                type="number"
                                step="any"
                                min="0"
                                inputmode="decimal"
                                :id="'item-quantity-'+index"
                                :name="'items['+index+'][quantity]'"
                                x-model="item.quantity"
                                required
                                class="block w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                            >
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-ink" :for="'item-unit-'+index">Unité</label>
                            <select
                                :id="'item-unit-'+index"
                                :name="'items['+index+'][unit_id]'"
                                x-model="item.unit_id"
                                class="block w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                            >
                                <option value="">— Défaut —</option>
                                <template x-for="(label, id) in unitOptions" :key="id">
                                    <option :value="id" x-text="label"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-ink" :for="'item-mode-'+index">Mode de déduction</label>
                            <select
                                :id="'item-mode-'+index"
                                :name="'items['+index+'][deduction_mode]'"
                                x-model="item.deduction_mode"
                                required
                                class="block w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                            >
                                <template x-for="(label, value) in modeOptions" :key="value">
                                    <option :value="value" x-text="label"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="isCostBased(item.deduction_mode)">
                            <label class="mb-1.5 block text-xs font-bold text-ink" :for="'item-cost-'+index">Coût unitaire (FCFA)</label>
                            <input
                                type="number"
                                min="0"
                                inputmode="numeric"
                                :id="'item-cost-'+index"
                                :name="'items['+index+'][unit_cost]'"
                                x-model="item.unit_cost"
                                :required="isCostBased(item.deduction_mode)"
                                class="block w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                            >
                        </div>

                        <x-ui.button type="button" variant="danger" size="sm" icon="trash-2" @click="removeItem(index)">
                            <span class="sr-only">Retirer la ressource</span>
                        </x-ui.button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('production.recipes.index')">Annuler</x-ui.button>
        <x-ui.button type="submit" variant="primary" icon="check">{{ $submitLabel }}</x-ui.button>
    </div>
</form>
