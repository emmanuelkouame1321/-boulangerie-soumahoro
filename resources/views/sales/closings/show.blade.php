@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Clôture {{ $closing->reference }}">
    <x-layout.page-header
        eyebrow="Commercial • {{ $closing->closing_date?->format('d/m/Y') }}"
        title="Clôture {{ $closing->reference }}"
        description="{{ $closing->bakery?->name ?? '—' }}"
    >
        <x-slot:actions>
            <x-ui.button variant="ghost" icon="arrow-left" :href="route('sales.closings.index')">Retour</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($closing->hasGap())
        <x-ui.alert tone="warning" title="Écart d'inventaire" class="mb-4">
            Un écart total de {{ Format::signed($closing->gap_total) }} unités a été constaté.
        </x-ui.alert>
    @endif

    <div class="mb-4 grid grid-cols-2 gap-3.5 xl:grid-cols-5">
        <x-ui.stat-card label="Produit" :value="Format::stock($closing->produced_total)" icon="factory" />
        <x-ui.stat-card label="Vendu" :value="Format::stock($closing->sold_total)" icon="receipt" />
        <x-ui.stat-card label="Restant" :value="Format::stock($closing->remaining_total)" icon="package" />
        <x-ui.stat-card label="Perdu / Retourné" :value="Format::stock($closing->lost_total).' / '.Format::stock($closing->returned_total)" icon="package-x" />
        <x-ui.stat-card label="Écart" :value="Format::signed($closing->gap_total)" icon="scale" />
    </div>

    <x-ui.card title="Détail par produit" flush>
        <x-ui.data-table :headers="['Produit', ['label' => 'Production', 'align' => 'right'], ['label' => 'Vendu', 'align' => 'right'], ['label' => 'Restant', 'align' => 'right'], ['label' => 'Perdu', 'align' => 'right'], ['label' => 'Retourné', 'align' => 'right'], ['label' => 'Écart', 'align' => 'right']]">
            @foreach ($closing->items as $item)
                <tr>
                    <td class="font-medium">{{ $item->product?->name ?? '—' }}</td>
                    <td class="numeric text-right">{{ Format::stock($item->produced_quantity) }}</td>
                    <td class="numeric text-right">{{ Format::stock($item->sold_quantity) }}</td>
                    <td class="numeric text-right">{{ Format::stock($item->remaining_quantity) }}</td>
                    <td class="numeric text-right">{{ Format::stock($item->lost_quantity) }}</td>
                    <td class="numeric text-right">{{ Format::stock($item->returned_quantity) }}</td>
                    <td class="numeric text-right {{ bccomp($item->gap, '0', 4) !== 0 ? 'font-bold text-warning' : 'text-muted' }}">
                        {{ Format::signed($item->gap) }}
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>
    </x-ui.card>
</x-layouts.app>
