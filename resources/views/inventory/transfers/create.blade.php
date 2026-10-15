@php
    $sourceOptions = $sites->mapWithKeys(static fn ($bakery): array => [$bakery->getKey() => $bakery->name])->all();
@endphp

<x-layouts.app title="Nouveau transfert">
    <x-layout.page-header
        eyebrow="Logistique"
        title="Nouveau transfert"
        description="Le stock n'est pas déplacé tout de suite : le transfert est d'abord un brouillon, expédié et réceptionné en deux temps."
    />

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert tone="danger" class="mb-4">Certaines informations sont incomplètes. Vérifiez le produit, les sites et la quantité.</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('inventory.transfers.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
            <h2 class="text-sm font-bold text-ink">Trajet</h2>
            <p class="mt-1 text-[12px] text-muted">Le site expéditeur et le site destinataire doivent être différents.</p>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.select
                    name="bakery_id"
                    label="Site expéditeur"
                    :options="$sourceOptions"
                    :selected="old('bakery_id', $sourceId)"
                    required
                />

                <x-ui.select
                    name="destination_bakery_id"
                    label="Site destinataire"
                    :options="$destinationOptions"
                    placeholder="— Choisir —"
                    :selected="old('destination_bakery_id')"
                    required
                />
            </div>
        </div>

        <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
            <h2 class="text-sm font-bold text-ink">Produit</h2>
            <p class="mt-1 text-[12px] text-muted">Laissez le lot vide pour une imputation FEFO automatique dans les lots du site expéditeur.</p>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.select
                    name="product_id"
                    label="Produit"
                    :options="$productOptions"
                    placeholder="— Choisir —"
                    :selected="old('product_id')"
                    required
                />

                <x-ui.form-field
                    name="quantity"
                    label="Quantité"
                    type="number"
                    :value="old('quantity')"
                    inputmode="decimal"
                    help="Dans l'unité principale du produit."
                    required
                />

                <x-ui.select
                    name="production_batch_id"
                    label="Lot (facultatif)"
                    :options="$batchOptions"
                    placeholder="— Imputation FEFO —"
                    :selected="old('production_batch_id')"
                />

                <x-ui.form-field
                    name="transferred_on"
                    label="Date du transfert"
                    type="date"
                    :value="old('transferred_on', now()->toDateString())"
                />

                <div class="sm:col-span-2">
                    <x-ui.form-field
                        name="reason"
                        label="Motif"
                        :value="old('reason')"
                        placeholder="Ex. : réapprovisionnement du site, rupture locale…"
                    />
                </div>
            </div>
        </div>

        <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
            <h2 class="text-sm font-bold text-ink">Observation</h2>
            <p class="mt-1 text-[12px] text-muted">Facultatif : transporteur, numéro de bordereau, contexte…</p>

            <div class="mt-4">
                <x-ui.form-field name="note" label="Note interne" :value="old('note')" />
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <x-ui.button variant="secondary" :href="route('inventory.transfers.index')">Annuler</x-ui.button>
            <x-ui.button type="submit" variant="primary">Préparer le transfert</x-ui.button>
        </div>
    </form>
</x-layouts.app>
