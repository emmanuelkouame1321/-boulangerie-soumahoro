@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Tableau de bord">
    <x-layout.page-header
        eyebrow="Vue opérationnelle • Aujourd’hui"
        title="Tableau de bord"
        description="Situation consolidée de toutes les boulangeries."
    >
        <x-slot:actions>
            <x-ui.button variant="primary" icon="scan-barcode" href="{{ route('pos') }}">Ouvrir la caisse</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        {{-- KPIs --}}
        <section aria-label="Indicateurs clés du jour">
            <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
                <x-ui.stat-card label="CA du jour" :value="Format::money(2_450_000)" trend="+8,2 % vs hier" trend-tone="success" icon="banknote" />
                <x-ui.stat-card label="Ventes" :value="Format::number(1_284)" trend="+5,4 %" trend-tone="success" icon="receipt" />
                <x-ui.stat-card label="Production" :value="Format::number(3_240)" unit="unités" trend="Rendement 97,3 %" trend-tone="success" icon="factory" />
                <x-ui.stat-card label="Stock critique" :value="Format::number(7)" trend="3 produits expirent bientôt" trend-tone="warning" icon="triangle-alert" />
            </div>
        </section>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1.5fr_1fr]">
            {{-- Ventes sur 7 jours --}}
            <x-ui.card title="Ventes sur 7 jours" subtitle="Chiffre d’affaires • FCFA">
                <x-slot:actions>
                    <x-ui.button variant="secondary" size="sm" onclick="window.toast('Période : 7 jours')">7 jours</x-ui.button>
                </x-slot:actions>

                <x-ui.bar-chart :values="[42, 58, 49, 71, 64, 83, 94, 78, 89, 73, 92, 100]" />
            </x-ui.card>

            {{-- Alertes prioritaires --}}
            <x-ui.card title="Alertes prioritaires" subtitle="Actions à traiter">
                <x-slot:actions>
                    <x-ui.button variant="secondary" size="sm" href="{{ route('notifications.index') }}">Voir tout</x-ui.button>
                </x-slot:actions>

                <div class="divide-y divide-line">
                    @foreach ([
                        ['Stock critique', 'Farine 50 kg • 8 sacs', 'danger', 'Critique'],
                        ['Péremption', 'Croissants • lot #20260930-002', 'warning', 'Attention'],
                        ['Commande', 'Fournisseur Ivoire • en retard', 'warning', 'Attention'],
                        ['Production', 'Écart de rendement • -8 pains', 'info', 'Info'],
                    ] as [$alertTitle, $alertMeta, $alertTone, $alertLabel])
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="text-[13px] font-bold text-ink">{{ $alertTitle }}</p>
                                <p class="truncate text-[11px] text-soft">{{ $alertMeta }}</p>
                            </div>
                            <x-ui.status-badge :tone="$alertTone" :label="$alertLabel" :icon="false" />
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-ui.card>
                <x-slot:actions>
                    <x-ui.status-badge tone="success" label="En cours" :icon="false" />
                </x-slot:actions>
                <p class="label-caps text-muted">Production aujourd’hui</p>
                <p class="numeric mt-1 text-3xl font-extrabold">3 240</p>
                <p class="mt-1 text-[11px] text-soft">unités produites • 3 330 attendues</p>
                <x-ui.progress :value="97" class="mt-3" />
            </x-ui.card>

            <x-ui.card>
                <x-slot:actions>
                    <x-ui.status-badge tone="warning" label="7 alertes" :icon="false" />
                </x-slot:actions>
                <p class="label-caps text-muted">Stocks</p>
                <p class="numeric mt-1 text-3xl font-extrabold">24 860</p>
                <p class="mt-1 text-[11px] text-soft">unités disponibles</p>
                <x-ui.button variant="secondary" size="sm" class="mt-3" href="{{ route('inventory.index') }}">Consulter les stocks</x-ui.button>
            </x-ui.card>

            <x-ui.card>
                <x-slot:actions>
                    <x-ui.status-badge tone="success" label="Ouverte" :icon="false" />
                </x-slot:actions>
                <p class="label-caps text-muted">Caisse</p>
                <p class="numeric mt-1 text-3xl font-extrabold">185</p>
                <p class="mt-1 text-[11px] text-soft">ventes depuis ouverture</p>
                <x-ui.button variant="primary" size="sm" class="mt-3" href="{{ route('pos') }}">Continuer la caisse</x-ui.button>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
