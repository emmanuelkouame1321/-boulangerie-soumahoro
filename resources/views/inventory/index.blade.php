@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Stocks">
    <x-layout.page-header eyebrow="Inventaire temps réel" title="Stocks">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="clipboard-list" onclick="window.toast('Nouvel inventaire créé')">Inventaire</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
            <x-ui.stat-card label="Disponible" :value="Format::number(24_860)" unit="unités" trend="Tous sites" trend-tone="success" icon="boxes" />
            <x-ui.stat-card label="Stock faible" :value="Format::number(12)" trend="à surveiller" trend-tone="warning" icon="triangle-alert" />
            <x-ui.stat-card label="Critique" :value="Format::number(7)" trend="action requise" trend-tone="danger" icon="octagon-alert" />
            <x-ui.stat-card label="En commande" :value="Format::number(4_280)" unit="unités" trend="en transit" trend-tone="success" icon="truck" />
        </div>

        <x-ui.card flush>
            <div class="flex flex-wrap gap-2 p-4">
                <x-ui.search-input placeholder="Produit, SKU, code-barres…" />
                <x-ui.select name="site" :options="['Boulangerie Centre' => 'Boulangerie Centre', 'Boulangerie Cocody' => 'Boulangerie Cocody']" placeholder="Tous les sites" class="min-w-44" />
                <x-ui.select name="state" :options="['Normal' => 'Normal', 'Faible' => 'Faible', 'Critique' => 'Critique', 'Rupture' => 'Rupture']" placeholder="Tous les états" class="min-w-44" />
                <x-ui.button variant="secondary" icon="download" onclick="window.toast('Export en préparation')">Exporter</x-ui.button>
            </div>

            <x-ui.data-table :headers="['Produit', 'Type', ['label' => 'Stock', 'align' => 'right'], ['label' => 'Seuil', 'align' => 'right'], ['label' => 'Réservé', 'align' => 'right'], ['label' => 'Disponible', 'align' => 'right'], 'État', '']">
                @foreach ([
                    ['Farine 50 kg', 'Matière première', '24 sacs', 10, 2, '22', 'success', 'Normal'],
                    ['Levure', 'Matière première', '8 paquets', 10, 0, '8', 'warning', 'Faible'],
                    ['Sel', 'Matière première', '2 sacs', 5, 0, '2', 'danger', 'Critique'],
                    ['Baguette', 'Produit fini', '86', 30, 0, '86', 'success', 'Normal'],
                    ['Croissant', 'Produit fini', '18', 25, 4, '14', 'warning', 'Faible'],
                    ['Gâteau chocolat', 'Produit fini', '0', 4, 0, '0', 'danger', 'Rupture'],
                ] as [$name, $type, $stock, $threshold, $reserved, $available, $tone, $state])
                    <tr>
                        <td>
                            <span class="block font-bold text-ink">{{ $name }}</span>
                            <span class="block text-[11px] text-soft">SKU-{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::slug($name)) }}</span>
                        </td>
                        <td>{{ $type }}</td>
                        <td class="numeric text-right">{{ $stock }}</td>
                        <td class="numeric text-right">{{ $threshold }}</td>
                        <td class="numeric text-right">{{ $reserved }}</td>
                        <td class="numeric text-right font-bold">{{ $available }}</td>
                        <td><x-ui.status-badge :tone="$tone" :label="$state" :icon="false" /></td>
                        <td><x-ui.button variant="secondary" size="sm" onclick="window.toast('Historique de {{ $name }}')">Mouvements</x-ui.button></td>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </x-ui.card>
    </div>
</x-layouts.app>
