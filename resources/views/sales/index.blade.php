@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Ventes">
    <x-layout.page-header
        eyebrow="Commercial"
        title="Ventes"
        description="Les ventes au détail enregistrées. Chaque vente validée a déduit le stock de produits finis."
    >
        <x-slot:actions>
            @if ($canCreate)
                <x-ui.button variant="secondary" icon="monitor-smartphone" :href="route('pos')">Caisse</x-ui.button>
                <x-ui.button variant="primary" icon="plus" :href="route('sales.create')">Nouvelle vente</x-ui.button>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    <x-ui.card flush>
        <form method="GET" action="{{ route('sales.index') }}" class="flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-ui.search-input name="search" :value="$search" placeholder="Référence ou client…" />
            </div>

            <div class="min-w-44">
                <x-ui.form-field name="date" label="Date" type="date" :value="$date" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>

            @if ($search !== '' || $date !== '')
                <x-ui.button variant="ghost" :href="route('sales.index')">Effacer</x-ui.button>
            @endif
        </form>

        @if ($sales->isEmpty())
            <div class="p-4">
                <x-ui.empty-state
                    icon="receipt"
                    title="{{ $search !== '' || $date !== '' ? 'Aucun résultat' : 'Aucune vente' }}"
                    description="Enregistrez une vente au détail ; le stock de produits finis se déduit automatiquement."
                >
                    @if ($canCreate)
                        <x-slot:action>
                            <x-ui.button variant="primary" icon="plus" :href="route('sales.create')">Nouvelle vente</x-ui.button>
                        </x-slot:action>
                    @endif
                </x-ui.empty-state>
            </div>
        @else
            <x-ui.data-table :headers="['Vente', 'Client', 'Site', 'Date', ['label' => 'Articles', 'align' => 'right'], 'Paiement', ['label' => 'Total', 'align' => 'right'], 'Statut', '']">
                @foreach ($sales as $sale)
                    @php
                        $method = $sale->payments->first()?->method;
                    @endphp
                    <tr>
                        <td class="font-bold">{{ $sale->reference }}</td>
                        <td>{{ $sale->customer?->name ?? 'Comptoir' }}</td>
                        <td>{{ $sale->bakery?->name ?? '—' }}</td>
                        <td>{{ $sale->sale_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="numeric text-right">{{ Format::number($sale->items_count) }}</td>
                        <td>{{ $method !== null ? ($paymentMethods[$method] ?? $method) : '—' }}</td>
                        <td class="numeric text-right font-bold">{{ Format::money($sale->total) }}</td>
                        <td>
                            <x-ui.status-badge :tone="$sale->status->tone()" :label="$sale->status->label()" :icon="false" />
                        </td>
                        <td>
                            <x-ui.button variant="secondary" size="sm" :href="route('sales.show', $sale)">Ouvrir</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>

            <div class="p-4">
                {{ $sales->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
