@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Mouvements de stock">
    <x-layout.page-header
        eyebrow="Grand livre"
        title="Mouvements de stock"
        description="Chaque entrée, sortie, vente ou ajustement est historisé. Aucun mouvement n’est jamais supprimé."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="download" :href="route('inventory.movements.export', array_merge(['format' => 'csv'], $exportQuery))">CSV</x-ui.button>
            <x-ui.button variant="secondary" icon="download" :href="route('inventory.movements.export', array_merge(['format' => 'xls'], $exportQuery))">Excel</x-ui.button>
            <x-ui.button variant="secondary" icon="arrow-left" :href="route('inventory.index')">Retour au stock</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card flush>
        <form method="GET" action="{{ route('inventory.movements') }}" class="flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-ui.search-input name="search" :value="$search" placeholder="Produit ou SKU…" />
            </div>

            <div class="min-w-48">
                <x-ui.select name="type" :options="$typeOptions" :selected="$type" placeholder="Tous les types" />
            </div>

            <div class="w-40">
                <x-ui.form-field name="from" label="Du" type="date" :value="$from" />
            </div>

            <div class="w-40">
                <x-ui.form-field name="to" label="Au" type="date" :value="$to" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>

            @if ($search !== '' || $type !== '' || $from !== '' || $to !== '')
                <x-ui.button variant="ghost" :href="route('inventory.movements')">Effacer</x-ui.button>
            @endif
        </form>

        @if ($movements->isEmpty())
            <div class="p-4">
                <x-ui.empty-state
                    icon="clipboard-list"
                    title="{{ $search !== '' || $type !== '' || $from !== '' || $to !== '' ? 'Aucun mouvement trouvé' : 'Aucun mouvement' }}"
                    description="Les entrées de stock, sorties production, ventes et ajustements apparaîtront ici."
                />
            </div>
        @else
            <x-ui.data-table :headers="['Date', 'Produit', 'Type', ['label' => 'Quantité', 'align' => 'right'], ['label' => 'Stock après', 'align' => 'right'], 'Par', 'Motif']">
                @foreach ($movements as $movement)
                    <tr>
                        <td class="whitespace-nowrap">
                            <span class="block text-ink">{{ $movement->occurred_at->format('d/m/Y') }}</span>
                            <span class="block text-[11px] text-soft">{{ $movement->occurred_at->format('H:i') }}</span>
                        </td>
                        <td>
                            <span class="block font-bold text-ink">{{ $movement->product?->name ?? '—' }}</span>
                            <span class="block text-[11px] text-soft">{{ $movement->product?->sku }}</span>
                        </td>
                        <td>
                            <x-business.stock-movement-badge :type="$movement->type" />
                        </td>
                        <td class="numeric text-right">
                            <span class="{{ $movement->type->increasesStock() ? 'text-brand-700' : 'text-ink' }}">
                                {{ $movement->type->increasesStock() ? '+' : '−' }}{{ Format::stock($movement->quantity) }}
                            </span>
                            <span class="text-soft">{{ $movement->unit?->symbol ?? $movement->product?->primaryUnit?->symbol }}</span>
                        </td>
                        <td class="numeric text-right">{{ Format::stock($movement->quantity_after) }}</td>
                        <td>{{ $movement->user?->name ?? '—' }}</td>
                        <td>
                            <span class="block text-ink">{{ $movement->reason ?? $movement->reference ?? '—' }}</span>
                            @if ($movement->reason && $movement->reference)
                                <span class="block text-[11px] text-soft">{{ $movement->reference }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>

            <div class="p-4">
                {{ $movements->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
