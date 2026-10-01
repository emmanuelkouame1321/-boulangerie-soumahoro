@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Déclarer une perte">
    <x-layout.page-header
        eyebrow="Contrôle des écarts"
        title="Déclarer une perte"
        description="La quantité déduite du stock est imputée aux lots du produit dans l'ordre FEFO (les lots expirés compris) et valorisée au prix d'achat."
    />

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert tone="danger" class="mb-4">Certaines informations sont incomplètes. Vérifiez le produit, la quantité et le motif.</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('losses.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                    label="Quantité perdue"
                    type="number"
                    :value="old('quantity')"
                    inputmode="decimal"
                    help="Dans l'unité principale du produit."
                    required
                />

                <x-ui.select
                    name="reason"
                    label="Motif"
                    :options="$reasonOptions"
                    placeholder="— Choisir —"
                    :selected="old('reason')"
                    required
                />

                <x-ui.form-field
                    name="comment"
                    label="Commentaire"
                    :value="old('comment')"
                    placeholder="Ex. : casse pendant le transport"
                />
            </div>
        </div>

        <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
            <h2 class="text-sm font-bold text-ink">Observation</h2>
            <p class="mt-1 text-[12px] text-muted">Facultatif : contexte de la perte, justificatif, numéro d'incident…</p>

            <div class="mt-4">
                <x-ui.form-field name="note" label="Note interne" :value="old('note')" />
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <x-ui.button variant="secondary" :href="route('losses.index')">Annuler</x-ui.button>
            <x-ui.button type="submit" variant="primary">Enregistrer la perte</x-ui.button>
        </div>
    </form>
</x-layouts.app>
