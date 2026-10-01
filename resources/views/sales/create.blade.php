<x-layouts.app title="Nouvelle vente">
    <x-layout.page-header
        eyebrow="Commercial"
        title="Nouvelle vente"
        description="Enregistrez une vente au détail. Le stock de produits finis est déduit automatiquement à la validation."
    />

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert tone="danger" class="mb-4">Certaines informations sont incomplètes. Vérifiez le client et les lignes de la vente.</x-ui.alert>
    @endif

    <form
        method="POST"
        action="{{ route('sales.store') }}"
        class="space-y-6"
        x-data="{
            options: {{ Js::from($productOptions) }},
            items: [{ key: 0, product_id: '', quantity: 1, unit_price: 0, discount: 0 }],
            addItem() { this.items.push({ key: Date.now(), product_id: '', quantity: 1, unit_price: 0, discount: 0 }); },
            removeItem(index) { if (this.items.length > 1) { this.items.splice(index, 1); } }
        }"
    >
        @csrf

        <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
            <h2 class="text-sm font-bold text-ink">En-tête</h2>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.select name="customer_id" label="Client" :options="$customerOptions" placeholder="Comptoir (sans client)" />

                @if ($currentBakeryId === null && count($bakeryOptions) > 1)
                    <x-ui.select name="bakery_id" label="Site" :options="$bakeryOptions" placeholder="— Choisir —" required />
                @endif

                <x-ui.select name="payment_method" label="Mode de paiement" :options="$paymentMethods" placeholder="— Choisir —" />

                <x-ui.form-field name="sold_at" label="Date" type="date" :value="now()->toDateString()" />
            </div>
        </div>

        <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-sm font-bold text-ink">Articles</h2>
                <x-ui.button type="button" variant="secondary" size="sm" icon="plus" @click="addItem()">Ajouter une ligne</x-ui.button>
            </div>

            <div class="mt-4 space-y-3">
                <template x-for="(item, index) in items" :key="item.key">
                    <div class="grid grid-cols-1 items-end gap-3 sm:grid-cols-[1fr_6rem_8rem_7rem_auto]">
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
                                type="number" step="any" min="0" inputmode="decimal"
                                :id="'item-quantity-'+index" :name="'items['+index+'][quantity]'"
                                x-model="item.quantity" required
                                class="block w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                            >
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-ink" :for="'item-price-'+index">Prix (FCFA)</label>
                            <input
                                type="number" min="0" inputmode="numeric"
                                :id="'item-price-'+index" :name="'items['+index+'][unit_price]'"
                                x-model="item.unit_price"
                                class="block w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                            >
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-ink" :for="'item-discount-'+index">Remise</label>
                            <input
                                type="number" min="0" inputmode="numeric"
                                :id="'item-discount-'+index" :name="'items['+index+'][discount]'"
                                x-model="item.discount"
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
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.form-field name="paid_amount" label="Montant payé (FCFA)" type="number" inputmode="numeric" help="Laisser vide pour encaisser le total." />
                <x-ui.form-field name="note" label="Observation" help="Facultatif." />
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <x-ui.button variant="secondary" :href="route('sales.index')">Annuler</x-ui.button>
            <x-ui.button type="submit" variant="primary">Enregistrer la vente</x-ui.button>
        </div>
    </form>
</x-layouts.app>
