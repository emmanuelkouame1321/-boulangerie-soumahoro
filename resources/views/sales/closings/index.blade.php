@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Clôtures journalières">
    <x-layout.page-header
        eyebrow="Commercial"
        title="Clôtures journalières"
        description="L'inventaire des produits finis en fin de journée : produit, vendu, restant, perdu, retourné et écart."
    >
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" :href="route('sales.closings.create')">Nouvelle clôture</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    <x-ui.card flush>
        @if ($closings->isEmpty())
            <div class="p-4">
                <x-ui.empty-state
                    icon="clipboard-list"
                    title="Aucune clôture"
                    description="Clôturez la journée pour confronter l'inventaire des produits finis aux données de production et de vente."
                >
                    <x-slot:action>
                        <x-ui.button variant="primary" icon="plus" :href="route('sales.closings.create')">Nouvelle clôture</x-ui.button>
                    </x-slot:action>
                </x-ui.empty-state>
            </div>
        @else
            <x-ui.data-table :headers="['Clôture', 'Site', 'Date', ['label' => 'Produit', 'align' => 'right'], ['label' => 'Vendu', 'align' => 'right'], ['label' => 'Écart', 'align' => 'right'], 'Statut', '']">
                @foreach ($closings as $closing)
                    <tr>
                        <td class="font-bold">{{ $closing->reference }}</td>
                        <td>{{ $closing->bakery?->name ?? '—' }}</td>
                        <td>{{ $closing->closing_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="numeric text-right">{{ Format::stock($closing->produced_total) }}</td>
                        <td class="numeric text-right">{{ Format::stock($closing->sold_total) }}</td>
                        <td class="numeric text-right {{ $closing->hasGap() ? 'font-bold text-warning' : 'text-muted' }}">
                            {{ Format::signed($closing->gap_total) }}
                        </td>
                        <td>
                            <x-ui.status-badge :tone="$closing->status->tone()" :label="$closing->status->label()" :icon="false" />
                        </td>
                        <td>
                            <x-ui.button variant="secondary" size="sm" :href="route('sales.closings.show', $closing)">Ouvrir</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>

            <div class="p-4">
                {{ $closings->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
