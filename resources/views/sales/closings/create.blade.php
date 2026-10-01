<x-layouts.app title="Nouvelle clôture">
    <x-layout.page-header
        eyebrow="Commercial"
        title="Nouvelle clôture"
        description="Confrontez l'inventaire des produits finis à la production et aux ventes du jour. L'écart est calculé automatiquement."
    />

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert tone="danger" class="mb-4">Certaines informations sont incomplètes. Vérifiez la date et les quantités.</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('sales.closings.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @if ($currentBakeryId === null && count($bakeryOptions) > 1)
                    <x-ui.select name="bakery_id" label="Site" :options="$bakeryOptions" placeholder="— Choisir —" required />
                @endif

                <x-ui.form-field name="closing_date" label="Date de clôture" type="date" :value="$date->toDateString()" required />
            </div>
        </div>

        <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
            <h2 class="text-sm font-bold text-ink">Inventaire des produits finis</h2>
            <p class="mt-1 text-[12px] text-muted">Saisissez les quantités restantes, perdues et retournées. Laissez « restant » vide pour reprendre le stock courant.</p>

            @if (empty($snapshot))
                <div class="mt-4">
                    <x-ui.empty-state
                        icon="package"
                        title="Aucun produit concerné"
                        description="Aucune production ni vente n'est enregistrée pour cette date."
                    />
                </div>
            @else
                <div class="mt-4">
                    <x-ui.data-table :headers="['Produit', ['label' => 'Produit', 'align' => 'right'], ['label' => 'Vendu', 'align' => 'right'], ['label' => 'Attendu', 'align' => 'right'], 'Restant', 'Perdu', 'Retourné']">
                        @foreach ($snapshot as $row)
                            <tr>
                                <td class="font-medium">{{ $row['product']->name }}</td>
                                <td class="numeric text-right">{{ \App\Support\Format::stock($row['produced']) }}</td>
                                <td class="numeric text-right">{{ \App\Support\Format::stock($row['sold']) }}</td>
                                <td class="numeric text-right text-muted">{{ \App\Support\Format::stock($row['expected_remaining']) }}</td>
                                <td>
                                    <input
                                        type="number" step="any" min="0" inputmode="decimal"
                                        name="entries[{{ $row['product']->getKey() }}][remaining]"
                                        value="{{ old('entries.'.$row['product']->getKey().'.remaining', $row['current_stock']) }}"
                                        class="w-24 rounded-control border border-line-strong bg-surface px-2 py-1.5 text-sm text-ink focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                                    >
                                </td>
                                <td>
                                    <input
                                        type="number" step="any" min="0" inputmode="decimal"
                                        name="entries[{{ $row['product']->getKey() }}][lost]"
                                        value="{{ old('entries.'.$row['product']->getKey().'.lost', 0) }}"
                                        class="w-20 rounded-control border border-line-strong bg-surface px-2 py-1.5 text-sm text-ink focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                                    >
                                </td>
                                <td>
                                    <input
                                        type="number" step="any" min="0" inputmode="decimal"
                                        name="entries[{{ $row['product']->getKey() }}][returned]"
                                        value="{{ old('entries.'.$row['product']->getKey().'.returned', 0) }}"
                                        class="w-20 rounded-control border border-line-strong bg-surface px-2 py-1.5 text-sm text-ink focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.data-table>
                </div>
            @endif
        </div>

        <div class="rounded-card border border-line bg-surface p-5 shadow-card sm:p-6">
            <x-ui.form-field name="note" label="Observation" help="Facultatif, sauf si un écart dépasse le seuil configuré : au-delà de {{ \App\Support\Format::number((string) config('boulangerie.day_closing.gap_justification_threshold', 10)) }} unités, l'observation devient obligatoire." />
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <x-ui.button variant="secondary" :href="route('sales.closings.index')">Annuler</x-ui.button>
            <x-ui.button type="submit" variant="primary">Enregistrer la clôture</x-ui.button>
        </div>
    </form>
</x-layouts.app>
