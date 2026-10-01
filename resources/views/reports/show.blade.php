@php
    use App\Support\Format;

    $exportQuery = ['from' => $from, 'to' => $to, 'site' => $site];
@endphp

<x-layouts.app title="Rapport — {{ $label }}">
    <x-layout.page-header
        eyebrow="Analyse & pilotage"
        title="{{ $label }}"
        description="{{ $description }} Du {{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }} au {{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}."
    >
        <x-slot:actions>
            @if ($canExport)
                <form method="POST" action="{{ route('exports.store') }}">
                    @csrf
                    <input type="hidden" name="kind" value="report:{{ $kind }}" />
                    <input type="hidden" name="format" value="csv" />
                    <input type="hidden" name="filters[from]" value="{{ $from }}" />
                    <input type="hidden" name="filters[to]" value="{{ $to }}" />
                    <input type="hidden" name="filters[site]" value="{{ $site }}" />
                    <x-ui.button type="submit" variant="secondary" icon="download">CSV</x-ui.button>
                </form>
                <form method="POST" action="{{ route('exports.store') }}">
                    @csrf
                    <input type="hidden" name="kind" value="report:{{ $kind }}" />
                    <input type="hidden" name="format" value="xls" />
                    <input type="hidden" name="filters[from]" value="{{ $from }}" />
                    <input type="hidden" name="filters[to]" value="{{ $to }}" />
                    <input type="hidden" name="filters[site]" value="{{ $site }}" />
                    <x-ui.button type="submit" variant="secondary" icon="download">Excel</x-ui.button>
                </form>
            @endif
            <x-ui.button variant="secondary" icon="printer" :href="route('reports.print', array_merge(['kind' => $kind], $exportQuery))" target="_blank">Imprimer / PDF</x-ui.button>
            <x-ui.button variant="primary" :href="route('reports.index', $exportQuery)">Tous les rapports</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        @if ($kind === 'sales')
            <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
                <x-ui.stat-card label="Chiffre d'affaires" :value="Format::money($data['revenue'])" />
                <x-ui.stat-card label="Ventes" :value="Format::number($data['sales'])" />
                <x-ui.stat-card label="Détail" :value="Format::money($data['retail_revenue'])" />
                <x-ui.stat-card label="Grossistes" :value="Format::money($data['wholesale_revenue'])" />
            </div>

            <x-ui.card title="Ventes par produit" flush>
                <x-ui.data-table :headers="['Produit', ['label' => 'Quantité', 'align' => 'right'], ['label' => 'Chiffre d’affaires', 'align' => 'right']]">
                    @foreach ($data['by_product'] as $row)
                        <tr>
                            <td class="font-bold">{{ $row['name'] }}</td>
                            <td class="numeric text-right">{{ Format::stock($row['quantity']) }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($row['revenue']) }}</td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            </x-ui.card>

            <x-ui.card title="Détail journalier" flush>
                <x-ui.data-table :headers="['Date', ['label' => 'Chiffre d’affaires', 'align' => 'right']]">
                    @foreach ($data['daily'] as $row)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($row['revenue']) }}</td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            </x-ui.card>
        @elseif ($kind === 'stock')
            <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-3">
                <x-ui.stat-card label="Valorisation" :value="Format::money($data['valuation'])" />
                <x-ui.stat-card label="Produits suivis" :value="Format::number($data['tracked'])" />
                <x-ui.stat-card label="Sous le seuil" :value="Format::number($data['below_threshold'])" trend-tone="warning" />
            </div>

            <x-ui.card title="Stock par produit" flush>
                <x-ui.data-table :headers="['Produit', ['label' => 'Quantité', 'align' => 'right'], ['label' => 'Seuil', 'align' => 'right'], ['label' => 'Valeur', 'align' => 'right']]">
                    @foreach ($data['by_product'] as $row)
                        <tr>
                            <td class="font-bold">{{ $row['name'] }}</td>
                            <td class="numeric text-right">{{ Format::stock($row['quantity']) }}</td>
                            <td class="numeric text-right">{{ $row['threshold'] !== null ? Format::stock($row['threshold']) : '—' }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($row['value']) }}</td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            </x-ui.card>
        @elseif ($kind === 'production')
            <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
                <x-ui.stat-card label="Produit" :value="Format::stock($data['produced'])" />
                <x-ui.stat-card label="Attendu" :value="Format::stock($data['expected'])" />
                <x-ui.stat-card label="Écart" :value="Format::signed($data['gap'])" />
                <x-ui.stat-card label="Coût" :value="Format::money($data['cost'])" />
            </div>

            <x-ui.card title="Production par produit" flush>
                <x-ui.data-table :headers="['Produit', ['label' => 'Quantité', 'align' => 'right']]">
                    @foreach ($data['by_product'] as $row)
                        <tr>
                            <td class="font-bold">{{ $row['name'] }}</td>
                            <td class="numeric text-right">{{ Format::stock($row['quantity']) }}</td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            </x-ui.card>
        @elseif ($kind === 'losses')
            <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-3">
                <x-ui.stat-card label="Valeur des pertes" :value="Format::money($data['value'])" />
                <x-ui.stat-card label="Quantité" :value="Format::stock($data['quantity'])" />
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <x-ui.card title="Pertes par motif" flush>
                    <x-ui.data-table :headers="['Motif', ['label' => 'Quantité', 'align' => 'right'], ['label' => 'Valeur', 'align' => 'right']]">
                        @foreach ($data['by_reason'] as $row)
                            <tr>
                                <td class="font-bold">{{ $row['reason'] }}</td>
                                <td class="numeric text-right">{{ Format::stock($row['quantity']) }}</td>
                                <td class="numeric text-right font-bold">{{ Format::money($row['value']) }}</td>
                            </tr>
                        @endforeach
                    </x-ui.data-table>
                </x-ui.card>

                <x-ui.card title="Pertes par produit" flush>
                    <x-ui.data-table :headers="['Produit', ['label' => 'Quantité', 'align' => 'right'], ['label' => 'Valeur', 'align' => 'right']]">
                        @foreach ($data['by_product'] as $row)
                            <tr>
                                <td class="font-bold">{{ $row['name'] }}</td>
                                <td class="numeric text-right">{{ Format::stock($row['quantity']) }}</td>
                                <td class="numeric text-right font-bold">{{ Format::money($row['value']) }}</td>
                            </tr>
                        @endforeach
                    </x-ui.data-table>
                </x-ui.card>
            </div>
        @elseif ($kind === 'purchases')
            <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-3">
                <x-ui.stat-card label="Total commandé" :value="Format::money($data['ordered_total'])" />
                <x-ui.stat-card label="Commandes en attente" :value="Format::number($data['pending_orders'])" />
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <x-ui.card title="Achats par fournisseur" flush>
                    <x-ui.data-table :headers="['Fournisseur', ['label' => 'Commandes', 'align' => 'right'], ['label' => 'Montant', 'align' => 'right']]">
                        @foreach ($data['by_supplier'] as $row)
                            <tr>
                                <td class="font-bold">{{ $row['name'] }}</td>
                                <td class="numeric text-right">{{ Format::number($row['orders']) }}</td>
                                <td class="numeric text-right font-bold">{{ Format::money($row['total']) }}</td>
                            </tr>
                        @endforeach
                    </x-ui.data-table>
                </x-ui.card>

                <x-ui.card title="Achats par produit" flush>
                    <x-ui.data-table :headers="['Produit', ['label' => 'Quantité', 'align' => 'right'], ['label' => 'Montant', 'align' => 'right']]">
                        @foreach ($data['by_product'] as $row)
                            <tr>
                                <td class="font-bold">{{ $row['name'] }}</td>
                                <td class="numeric text-right">{{ Format::stock($row['quantity']) }}</td>
                                <td class="numeric text-right font-bold">{{ Format::money($row['total']) }}</td>
                            </tr>
                        @endforeach
                    </x-ui.data-table>
                </x-ui.card>
            </div>
        @elseif ($kind === 'wholesalers')
            <x-ui.card title="Grossistes de l'exercice" flush>
                <x-ui.data-table :headers="['Grossiste', ['label' => 'Quantité', 'align' => 'right'], ['label' => 'Chiffre d’affaires', 'align' => 'right'], ['label' => 'Ristourne', 'align' => 'right']]">
                    @foreach ($data['rows'] as $row)
                        <tr>
                            <td class="font-bold">{{ $row['name'] }}</td>
                            <td class="numeric text-right">{{ Format::stock($row['quantity']) }}</td>
                            <td class="numeric text-right">{{ Format::money($row['revenue']) }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($row['rebate']) }}</td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            </x-ui.card>
        @elseif ($kind === 'expenses')
            <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-3">
                <x-ui.stat-card label="Total des charges" :value="Format::money($data['total'])" />
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <x-ui.card title="Charges par catégorie" flush>
                    <x-ui.data-table :headers="['Catégorie', ['label' => 'Nombre', 'align' => 'right'], ['label' => 'Montant', 'align' => 'right']]">
                        @foreach ($data['by_category'] as $row)
                            <tr>
                                <td class="font-bold">{{ $row['name'] }}</td>
                                <td class="numeric text-right">{{ Format::number($row['count']) }}</td>
                                <td class="numeric text-right font-bold">{{ Format::money($row['total']) }}</td>
                            </tr>
                        @endforeach
                    </x-ui.data-table>
                </x-ui.card>

                <x-ui.card title="Évolution mensuelle" flush>
                    <x-ui.data-table :headers="['Mois', ['label' => 'Montant', 'align' => 'right']]">
                        @foreach ($data['monthly'] as $row)
                            <tr>
                                <td class="font-bold">{{ $row['month'] }}</td>
                                <td class="numeric text-right font-bold">{{ Format::money($row['total']) }}</td>
                            </tr>
                        @endforeach
                    </x-ui.data-table>
                </x-ui.card>
            </div>
        @elseif ($kind === 'payroll')
            <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-3">
                <x-ui.stat-card label="Masse salariale" :value="Format::money($data['total_net'])" />
                <x-ui.stat-card label="Salaires de base" :value="Format::money($data['total_base'])" />
            </div>

            <x-ui.card title="Périodes de paie" flush>
                <x-ui.data-table :headers="['Période', ['label' => 'Net', 'align' => 'right'], 'Statut']">
                    @foreach ($data['periods'] as $row)
                        <tr>
                            <td class="font-bold">{{ $row['label'] }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($row['net']) }}</td>
                            <td>{{ $row['status'] }}</td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            </x-ui.card>
        @endif
    </div>
</x-layouts.app>
