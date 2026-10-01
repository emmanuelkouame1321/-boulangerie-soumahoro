@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Notifications">
    <x-layout.page-header
        eyebrow="Centre d’alertes"
        title="Notifications"
        description="Alertes de stock, dates limites, pertes et écarts de rendement. Une alerte à condition se referme seule quand la situation est résolue."
    />

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
            <x-ui.stat-card label="Alertes ouvertes" :value="Format::number($openCount)" :trend="$openCount > 0 ? 'à traiter' : 'tout est calme'" :trend-tone="$openCount > 0 ? 'danger' : 'muted'" icon="bell" />
            <x-ui.stat-card label="Total affiché" :value="Format::number($alerts->total())" icon="list" />
        </div>

        <x-ui.card flush>
            <form method="GET" action="{{ route('notifications.index') }}" class="flex flex-wrap items-end gap-3 p-4">
                <div class="min-w-44">
                    <x-ui.select name="status" label="Statut" :options="$statusOptions" placeholder="— Tous —" :selected="$status" />
                </div>

                <div class="min-w-44">
                    <x-ui.select name="type" label="Type" :options="$typeOptions" placeholder="— Tous —" :selected="$type" />
                </div>

                <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>

                @if ($status !== 'open' && $status !== '' || $type !== '')
                    <x-ui.button variant="ghost" :href="route('notifications.index')">Effacer</x-ui.button>
                @endif
            </form>

            @if ($alerts->isEmpty())
                <div class="p-4">
                    <x-ui.empty-state
                        icon="bell"
                        title="Aucune alerte"
                        description="Les seuils de stock, les dates limites et les écarts déclenchent automatiquement des alertes ici."
                    />
                </div>
            @else
                <div class="divide-y divide-line">
                    @foreach ($alerts as $alert)
                        <div class="flex flex-wrap items-start gap-3 px-4 py-4">
                            <x-ui.status-badge :tone="$alert->type->tone()" :label="$alert->type->label()" :icon="false" />

                            <div class="min-w-0 flex-1">
                                <p class="text-[13px] font-bold text-ink">
                                    {{ $alert->product?->name ?? 'Produit' }}
                                    @if ($alert->observed_value !== null)
                                        <span class="font-normal text-muted">— observé : {{ Format::stock($alert->observed_value) }}</span>
                                    @endif
                                </p>
                                <p class="text-[13px] text-muted">{{ $alert->message }}</p>
                                <p class="mt-0.5 text-[11px] text-soft">Déclenchée {{ $alert->triggered_at?->diffForHumans() ?? '—' }}</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <x-ui.status-badge :tone="$alert->status->tone()" :label="$alert->status->label()" :icon="false" />

                                @if ($canAcknowledge && $alert->status->value === 'open')
                                    <form method="POST" action="{{ route('notifications.acknowledge', $alert) }}">
                                        @csrf
                                        <x-ui.button type="submit" size="sm" variant="secondary">Acquitter</x-ui.button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-4">
                    {{ $alerts->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>
</x-layouts.app>
