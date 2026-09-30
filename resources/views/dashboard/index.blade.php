@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Tableau de bord">
    <x-layout.page-header
        title="Vue d’ensemble"
        description="Situation consolidée de toutes les boulangeries."
        :breadcrumbs="[['label' => 'Accueil'], ['label' => 'Vue d’ensemble']]"
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="download">Exporter</x-ui.button>
            <x-ui.button variant="primary" icon="plus">Nouvelle production</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-6">
        {{-- Ligne 1 — KPIs (Design System §15) --}}
        <section aria-labelledby="kpi-titre">
            <h2 id="kpi-titre" class="sr-only">Indicateurs clés du jour</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <x-ui.stat-card label="CA du jour" :value="Format::money(1_245_000)" trend="+8 % vs hier" trend-tone="success" icon="receipt" />
                <x-ui.stat-card label="Ventes du jour" :value="Format::number(4_320)" unit="pains" trend="+3 % vs hier" trend-tone="success" icon="trending-up" />
                <x-ui.stat-card label="Production du jour" :value="Format::number(5_100)" unit="pains" :meta="'Rendement ' . Format::percent(98)" icon="factory" />
                <x-ui.stat-card label="Stock critique" :value="Format::number(3)" unit="articles" trend="Action requise" trend-tone="danger" icon="triangle-alert" />
                <x-ui.stat-card label="Pertes du jour" :value="Format::money(32_500)" trend="-12 % vs hier" trend-tone="warning" icon="trending-down" />
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            {{-- Alertes opérationnelles --}}
            <section aria-labelledby="alertes-titre" class="xl:col-span-2">
                <h2 id="alertes-titre" class="mb-3 text-lg font-semibold text-ink">Alertes opérationnelles</h2>

                <div class="space-y-3">
                    <x-ui.alert tone="danger" title="Production impossible : stock insuffisant">
                        Il manque <span class="numeric font-medium">3 sacs</span> de farine à la Boulangerie Cocody.
                    </x-ui.alert>

                    <x-ui.alert tone="warning" title="Consommation cumulée atteinte">
                        Le seuil cumulé du fagot est dépassé sur la période en cours.
                    </x-ui.alert>

                    <x-ui.alert tone="info" title="Commande en transit">
                        La commande <span class="numeric">CMD-2026-0142</span> est attendue aujourd’hui.
                    </x-ui.alert>
                </div>
            </section>

            {{-- État métier de référence --}}
            <section aria-labelledby="etats-titre">
                <h2 id="etats-titre" class="mb-3 text-lg font-semibold text-ink">États métier</h2>

                <div class="rounded-card border border-line bg-surface p-5 shadow-card">
                    <ul class="space-y-3 text-sm">
                        @foreach ([['stock' => 'available'], ['stock' => 'low'], ['stock' => 'critical'], ['production' => 'validated'], ['production' => 'in_progress'], ['order' => 'pending'], ['batch' => 'to_sell_soon'], ['batch' => 'expired']] as $entry)
                            @foreach ($entry as $status)
                                <li class="flex items-center justify-between gap-3">
                                    <span class="text-muted">{{ $status }}</span>
                                    <x-ui.status-badge :status="$status" />
                                </li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            </section>
        </div>

        {{-- État vide — référence d’implémentation --}}
        <section aria-labelledby="recent-titre">
            <h2 id="recent-titre" class="mb-3 text-lg font-semibold text-ink">Dernières productions</h2>

            <x-ui.empty-state
                title="Aucune production aujourd’hui"
                description="Aucune production n’a encore été enregistrée pour cette boulangerie."
                icon="factory"
            >
                <x-slot:action>
                    <x-ui.button variant="primary" icon="plus">Créer une production</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        </section>
    </div>
</x-layouts.app>
