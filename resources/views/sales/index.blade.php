@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Ventes">
    <x-layout.page-header eyebrow="Commercial • Aujourd’hui" title="Ventes">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="monitor-smartphone" href="{{ route('pos') }}">Nouvelle vente</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
            <x-ui.stat-card label="CA du jour" :value="Format::money(2_450_000)" trend="1 284 ventes" trend-tone="success" icon="banknote" />
            <x-ui.stat-card label="Particuliers" :value="Format::money(1_720_000)" trend="70 % du CA" trend-tone="success" icon="users" />
            <x-ui.stat-card label="Grossistes" :value="Format::money(730_000)" trend="30 % du CA" trend-tone="success" icon="store" />
            <x-ui.stat-card label="Créances" :value="Format::money(185_000)" trend="4 comptes" trend-tone="warning" icon="circle-dollar-sign" />
        </div>

        <x-ui.card flush>
            <div class="flex flex-wrap gap-2 p-4">
                <x-ui.search-input placeholder="N° vente, client, caissier…" />
                <x-ui.button variant="secondary" onclick="window.toast('Filtre : aujourd’hui')">Aujourd’hui</x-ui.button>
                <x-ui.button variant="secondary" onclick="window.toast('Sélecteur de période')">Période</x-ui.button>
                <x-ui.button variant="secondary" icon="download" onclick="window.toast('Export en préparation')">Exporter</x-ui.button>
            </div>

            <x-ui.data-table :headers="['Vente', 'Client', ['label' => 'Articles', 'align' => 'right'], 'Paiement', ['label' => 'Total', 'align' => 'right'], 'Caissier', 'Statut']">
                @foreach ([
                    ['SALE-00045', 'Comptoir', 6, 'Espèces', 3_300, 'Awa', 'success', 'Validée'],
                    ['SALE-00044', 'Boulangerie ABC', 120, 'Virement', 96_000, 'Kouassi', 'success', 'Validée'],
                    ['SALE-00043', 'Comptoir', 4, 'Mobile Money', 4_200, 'Awa', 'success', 'Validée'],
                    ['SALE-00042', 'Hôtel XYZ', 38, 'À crédit', 42_000, 'Kouassi', 'warning', 'En attente'],
                ] as [$ref, $customer, $items, $payment, $total, $cashier, $tone, $status])
                    <tr>
                        <td class="font-bold">{{ $ref }}</td>
                        <td>{{ $customer }}</td>
                        <td class="numeric text-right">{{ $items }}</td>
                        <td>{{ $payment }}</td>
                        <td class="numeric text-right font-bold">{{ Format::money($total) }}</td>
                        <td>{{ $cashier }}</td>
                        <td><x-ui.status-badge :tone="$tone" :label="$status" :icon="false" /></td>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </x-ui.card>
    </div>
</x-layouts.app>
