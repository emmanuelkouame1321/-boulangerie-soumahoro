@php
    use App\Enums\ProductType;
    use App\Support\Format;
@endphp

<x-layouts.app title="Stocks">
    <x-layout.page-header
        eyebrow="Inventaire temps réel"
        title="Stocks"
        description="Le stock physique et le disponible par produit et par site. Toute variation passe par un mouvement traçable."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="clipboard-list" :href="route('inventory.movements')">Mouvements</x-ui.button>

            @if ($canAdjust)
                <x-ui.button variant="primary" icon="plus" x-data @click="$dispatch('open-modal', 'stock-adjustment')">Ajuster</x-ui.button>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    @php
        // Chaque compteur renvoie vers la liste filtrée correspondante,
        // en conservant la recherche en cours.
        $stateUrl = static fn (string $state): string => route('inventory.index', array_filter(
            ['state' => $state, 'search' => $search],
            static fn (string $value): bool => $value !== '',
        ));
    @endphp

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
            <x-ui.stat-card label="Produits suivis" :value="Format::number($counts['tracked'])" unit="références" icon="boxes" :href="$stateUrl('')" />
            <x-ui.stat-card label="Stock faible" :value="Format::number($counts['low'])" trend="à surveiller" trend-tone="warning" icon="triangle-alert" :href="$stateUrl('low')" />
            <x-ui.stat-card label="Critique" :value="Format::number($counts['critical'])" trend="action requise" trend-tone="danger" icon="octagon-alert" :href="$stateUrl('critical')" />
            <x-ui.stat-card label="Réservé" :value="Format::stock($counts['reserved'])" unit="unités" icon="package" />
        </div>

        <x-ui.card flush>
            <form method="GET" action="{{ route('inventory.index') }}" class="flex flex-wrap items-end gap-3 p-4">
                <div class="min-w-56 flex-1">
                    <x-ui.search-input name="search" :value="$search" placeholder="Produit ou SKU…" />
                </div>

                <div class="min-w-44">
                    <x-ui.select name="state" :options="$stateOptions" :selected="$state" placeholder="Tous les états" />
                </div>

                <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>

                @if ($search !== '' || $state !== '')
                    <x-ui.button variant="ghost" :href="route('inventory.index')">Effacer</x-ui.button>
                @endif
            </form>

            @if ($stocks->isEmpty())
                <div class="p-4">
                    <x-ui.empty-state
                        icon="boxes"
                        title="{{ $search !== '' || $state !== '' ? 'Aucun résultat' : 'Aucun stock enregistré' }}"
                        description="Le stock d’un produit apparaît dès son premier mouvement (réception, production, ajustement)."
                    />
                </div>
            @else
                @php
                    $headers = ['Produit'];

                    if ($currentBakeryId === null) {
                        $headers[] = 'Site';
                    }

                    $headers = array_merge($headers, [
                        ['label' => 'Stock', 'align' => 'right'],
                        ['label' => 'Réservé', 'align' => 'right'],
                        ['label' => 'Disponible', 'align' => 'right'],
                        'DLC',
                        'État',
                        '',
                    ]);
                @endphp

                <x-ui.data-table :headers="$headers">
                    @foreach ($stocks as $row)
                        <tr>
                            <td>
                                <span class="block font-bold text-ink">{{ $row['product']->name }}</span>
                                <span class="block text-[11px] text-soft">{{ $row['product']->sku }}</span>
                            </td>

                            @if ($currentBakeryId === null)
                                <td>{{ $row['stock']->bakery?->name ?? '—' }}</td>
                            @endif

                            <td class="numeric text-right">
                                {{ Format::stock($row['quantity']) }}
                                <span class="text-soft">{{ $row['product']->primaryUnit?->symbol }}</span>
                            </td>
                            <td class="numeric text-right">{{ Format::stock($row['reserved']) }}</td>
                            <td class="numeric text-right font-bold">{{ Format::stock($row['available']) }}</td>
                            <td>
                                @if ($row['product']->type === ProductType::FinishedGood)
                                    <x-business.expiry-indicator :shelf-life-days="$row['product']->shelf_life_days" />
                                @else
                                    <span class="text-soft">—</span>
                                @endif
                            </td>
                            <td>
                                <x-business.stock-status :status="$row['status']" />
                            </td>
                            <td>
                                <x-ui.button
                                    variant="secondary"
                                    size="sm"
                                    :href="route('inventory.movements', ['search' => $row['product']->sku])"
                                >Mouvements</x-ui.button>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.data-table>

                <div class="p-4">
                    {{ $stocks->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>

    @if ($canAdjust)
        <x-ui.modal name="stock-adjustment" title="Ajuster un stock" subtitle="Chaque ajustement est un mouvement daté et motivé.">
            <form method="POST" action="{{ route('inventory.adjustments.store') }}" class="space-y-4">
                @csrf

                <x-ui.select name="product_id" label="Produit" :options="$productOptions" required />

                @if ($currentBakeryId === null && count($bakeryOptions) > 1)
                    <x-ui.select name="bakery_id" label="Site" :options="$bakeryOptions" required />
                @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.select
                        name="type"
                        label="Sens"
                        :options="['adjustment_in' => 'Entrée (ajustement positif)', 'adjustment_out' => 'Sortie (ajustement négatif)']"
                        required
                    />
                    <x-ui.form-field name="quantity" label="Quantité" type="number" step="any" inputmode="decimal" required />
                </div>

                <x-ui.form-field name="reason" label="Motif" help="Obligatoire : inventaire, casse, erreur de saisie…" required />
                <x-ui.form-field name="reference" label="Référence" help="Facultatif." />

                <div>
                    <label for="field-comment" class="mb-1.5 block text-xs font-bold text-ink">Commentaire</label>
                    <textarea
                        id="field-comment"
                        name="comment"
                        rows="2"
                        class="block w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink transition placeholder:text-soft focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                    >{{ old('comment') }}</textarea>
                </div>

                <div class="flex justify-end gap-2 border-t border-line pt-4">
                    <x-ui.button type="submit" variant="primary">Enregistrer l’ajustement</x-ui.button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</x-layouts.app>
