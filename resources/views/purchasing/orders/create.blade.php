<x-layouts.app title="Nouvelle commande">
    <x-layout.page-header
        eyebrow="Approvisionnement"
        title="Nouvelle commande"
        description="Sélectionnez le fournisseur et les matières commandées. La commande est créée au statut « commandée »."
    />

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert tone="danger" class="mb-4">Certaines informations sont incomplètes. Vérifiez le fournisseur et les lignes de la commande.</x-ui.alert>
    @endif

    <form
        method="POST"
        action="{{ route('purchasing.orders.store') }}"
        class="space-y-6"
        x-data="{
            options: {{ Js::from($productOptions) }},
            items: [{ key: 0, product_id: '', quantity: 1, unit_price: 0 }],
            addItem() { this.items.push({ key: Date.now(), product_id: '', quantity: 1, unit_price: 0 }); },
            removeItem(index) { if (this.items.length > 1) { this.items.splice(index, 1); } }
        }"
    >
        @csrf

        <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
            <h2 class="text-sm font-bold text-ink">En-tête</h2>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-ui.select name="supplier_id" label="Fournisseur" :options="$supplierOptions" placeholder="— Choisir —" required />

                @if ($currentBakeryId === null && count($bakeryOptions) > 1)
                    <x-ui.select name="bakery_id" label="Site" :options="$bakeryOptions" placeholder="— Choisir —" required />
                @endif

                <x-ui.form-field name="expected_at" label="Livraison attendue" type="date" />
            </div>
        </div>

        <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-bold text-ink">Lignes de commande</h2>
                <x-ui.button type="button" variant="secondary" size="sm" icon="plus" @click="addItem()">Ajouter une ligne</x-ui.button>
            </div>

            <div class="mt-4 space-y-3">
                <template x-for="(item, index) in items" :key="item.key">
                    <div class="grid grid-cols-1 items-end gap-3 sm:grid-cols-[1fr_8rem_10rem_auto]">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-ink" :for="'item-product-'+index">Produit</label>
                            <select
                                :id="'item-product-'+index"
                                :name="'items['+index+'][product_id]'"
                                x-model="item.product_id"
                                required
                                class="block w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                            >
                                <option value="">— Choisir —</option>
                                <template x-for="(label, id) in options" :key="id">
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
                            <label class="mb-1.5 block text-xs font-bold text-ink" :for="'item-price-'+index">Prix unitaire (FCFA)</label>
                            <input
                                type="number"
                                min="0"
                                inputmode="numeric"
                                :id="'item-price-'+index"
                                :name="'items['+index+'][unit_price]'"
                                x-model="item.unit_price"
                                required
                                class="block w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                            >
                        </div>

                        <x-ui.button type="button" variant="danger" size="sm" icon="trash-2" @click="removeItem(index)">
                            <span class="sr-only">Retirer la ligne</span>
                        </x-ui.button>
                    </div>
                </template>
            </div>
        </div>

        <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
            <x-ui.form-field name="note" label="Observation" help="Facultatif." />
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <x-ui.button variant="secondary" :href="route('purchasing.orders.index')">Annuler</x-ui.button>
            <x-ui.button type="submit" variant="primary">Créer la commande</x-ui.button>
        </div>
    </form>
</x-layouts.app>
