@php
    use App\Support\Format;

    $maxTrend = max(1, max(array_map(static fn ($point) => $point['revenue'], $trend ?: [['revenue' => 1]])));
    $trendValues = array_map(static fn ($point) => (int) round($point['revenue'] / $maxTrend * 100), $trend);

    $titles = [
        'super-admin' => ['title' => 'Tableau de bord', 'eyebrow' => 'Vue consolidée', 'description' => 'Situation consolidée de toutes les boulangeries.'],
        'production' => ['title' => 'Tableau de bord production', 'eyebrow' => 'Atelier', 'description' => 'Production, écarts et matière première du site.'],
        'sales' => ['title' => 'Tableau de bord vente', 'eyebrow' => 'Commercial', 'description' => 'Ventes et chiffre d’affaires du site.'],
    ][$mode];
@endphp

<x-layouts.app title="{{ $titles['title'] }}">
    <x-layout.page-header
        eyebrow="{{ $titles['eyebrow'] }} • {{ $bakery?->name ?? 'Tous les sites' }}"
        title="{{ $titles['title'] }}"
        description="{{ $titles['description'] }}"
    >
        <x-slot:actions>
            @can('report.view')
                <x-ui.button variant="secondary" icon="bar-chart-3" :href="route('reports.index')">Rapports</x-ui.button>
            @endcan
            <x-ui.button variant="primary" icon="scan-barcode" :href="route('pos')">Ouvrir la caisse</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        {{-- Identité du site : nom, logo et accès direct aux paramètres
             (ADR-033 — la modification n'est ouverte qu'au Super-Admin).
             Visible seulement quand un site précis est ouvert. --}}
        @if ($bakery !== null)
            <section aria-label="Identité du site">
                <x-ui.card>
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="grid size-14 shrink-0 place-items-center overflow-hidden rounded-card border border-line bg-subtle text-lg font-extrabold text-brand-700">
                            @if ($bakery->logoUrl() !== null)
                                <img src="{{ $bakery->logoUrl() }}" alt="Logo de {{ $bakery->name }}" class="size-full object-cover">
                            @else
                                {{ $bakery->initial() }}
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <p class="label-caps text-muted">Site courant</p>
                            <p class="truncate text-lg font-extrabold text-ink">{{ $bakery->name }}</p>
                            <p class="truncate text-xs text-muted">
                                {{ $bakery->code }}@if ($bakery->address) · {{ $bakery->address }} @endif
                            </p>
                        </div>

                        @can('settings.view')
                            <x-ui.button variant="secondary" icon="settings" :href="route('settings.index')">
                                Paramètres du site
                            </x-ui.button>
                        @endcan
                    </div>
                </x-ui.card>
            </section>
        @endif

        <section aria-label="Indicateurs clés du jour">
            <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
                <x-ui.stat-card label="CA du jour" :value="Format::money($salesToday['revenue'])" icon="banknote" />
                <x-ui.stat-card label="Ventes" :value="Format::number($salesToday['sales'])" icon="receipt" />
                <x-ui.stat-card label="Production du jour" :value="Format::stock($productionToday['produced'])" unit="unités" :trend="'Attendu '.Format::stock($productionToday['expected'])" icon="factory" />
                <x-ui.stat-card
                    label="Alertes ouvertes"
                    :value="Format::number($openAlertCount)"
                    :trend="$expiringCount.' péremption'"
                    :trend-tone="$expiringCount > 0 ? 'warning' : 'muted'"
                    icon="triangle-alert"
                />
            </div>
        </section>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1.5fr_1fr]">
            <x-ui.card title="Ventes sur 7 jours" subtitle="Chiffre d'affaires • FCFA">
                <x-ui.bar-chart :values="$trendValues" />
            </x-ui.card>

            <x-ui.card title="Alertes prioritaires" subtitle="Actions à traiter">
                <x-slot:actions>
                    <x-ui.button variant="secondary" size="sm" :href="route('notifications.index')">Voir tout</x-ui.button>
                </x-slot:actions>

                @if ($alerts->isEmpty())
                    <p class="py-4 text-sm text-muted">Aucune alerte ouverte.</p>
                @else
                    <div class="divide-y divide-line">
                        @foreach ($alerts as $alert)
                            <div class="flex items-center justify-between gap-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="text-[13px] font-bold text-ink">{{ $alert->type->label() }}</p>
                                    <p class="truncate text-[11px] text-soft">{{ $alert->message ?? $alert->product?->name ?? '—' }}</p>
                                </div>
                                <x-ui.status-badge :tone="$alert->type->tone()" :label="$alert->type->label()" :icon="false" />
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>

        @if ($mode === 'super-admin')
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <x-ui.card>
                    <p class="label-caps text-muted">CA du mois</p>
                    <p class="numeric mt-1 text-2xl font-extrabold text-ink">{{ Format::money($monthRevenue) }}</p>
                </x-ui.card>
                <x-ui.card>
                    <p class="label-caps text-muted">Charges du mois</p>
                    <p class="numeric mt-1 text-2xl font-extrabold text-ink">{{ Format::money($expensesMonth) }}</p>
                    <p class="mt-1 text-[11px] text-soft">Pertes : {{ Format::money($lossesMonth) }}</p>
                </x-ui.card>
                <x-ui.card>
                    <p class="label-caps text-muted">Masse salariale annuelle</p>
                    <p class="numeric mt-1 text-2xl font-extrabold text-ink">{{ Format::money($payrollNet) }}</p>
                </x-ui.card>
            </div>

            @if ($siteRevenue !== [])
                <x-ui.card title="Chiffre d'affaires du jour par site" flush>
                    <x-ui.data-table :headers="['Site', ['label' => 'CA du jour', 'align' => 'right']]">
                        @foreach ($siteRevenue as $site)
                            <tr>
                                <td class="font-bold">{{ $site['name'] }}</td>
                                <td class="numeric text-right font-bold">{{ Format::money($site['revenue']) }}</td>
                            </tr>
                        @endforeach
                    </x-ui.data-table>
                </x-ui.card>
            @endif
        @endif

        @if ($mode === 'production')
            <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
                <x-ui.stat-card label="Produit aujourd'hui" :value="Format::stock($productionToday['produced'])" />
                <x-ui.stat-card label="Attendu" :value="Format::stock($productionToday['expected'])" />
                <x-ui.stat-card label="Écart" :value="Format::signed($productionToday['gap'])" />
                <x-ui.stat-card
                    label="Produits sous seuil"
                    :value="Format::number($stock['below_threshold'])"
                    trend-tone="warning"
                    :href="route('inventory.index', ['state' => 'low'])"
                />
            </div>

            <x-ui.card title="Stock de matières" subtitle="Valorisation au coût d'achat." flush>
                <x-ui.data-table :headers="['Produit', ['label' => 'Quantité', 'align' => 'right'], ['label' => 'Seuil', 'align' => 'right'], ['label' => 'Valeur', 'align' => 'right']]">
                    @foreach (array_slice($stock['by_product'], 0, 10) as $row)
                        <tr>
                            <td class="font-bold">{{ $row['name'] }}</td>
                            <td class="numeric text-right">{{ Format::stock($row['quantity']) }}</td>
                            <td class="numeric text-right">{{ $row['threshold'] !== null ? Format::stock($row['threshold']) : '—' }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($row['value']) }}</td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            </x-ui.card>
        @endif

        @if ($mode === 'sales')
            <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-3">
                <x-ui.stat-card label="CA du mois" :value="Format::money($monthRevenue)" />
                <x-ui.stat-card label="Détail du jour" :value="Format::money($salesToday['retail_revenue'])" />
                <x-ui.stat-card label="Grossistes du jour" :value="Format::money($salesToday['wholesale_revenue'])" />
            </div>

            <x-ui.card title="Meilleures ventes du jour" flush>
                @if ($topProducts === [])
                    <div class="p-4">
                        <x-ui.empty-state icon="receipt" title="Aucune vente aujourd'hui" description="Les ventes du jour apparaîtront ici." />
                    </div>
                @else
                    <x-ui.data-table :headers="['Produit', ['label' => 'Quantité', 'align' => 'right'], ['label' => 'Chiffre d’affaires', 'align' => 'right']]">
                        @foreach ($topProducts as $row)
                            <tr>
                                <td class="font-bold">{{ $row['name'] }}</td>
                                <td class="numeric text-right">{{ Format::stock($row['quantity']) }}</td>
                                <td class="numeric text-right font-bold">{{ Format::money($row['revenue']) }}</td>
                            </tr>
                        @endforeach
                    </x-ui.data-table>
                @endif
            </x-ui.card>
        @endif
    </div>
</x-layouts.app>
