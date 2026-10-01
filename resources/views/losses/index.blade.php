@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Pertes & invendus">
    <x-layout.page-header eyebrow="Contrôle des écarts" title="Pertes & invendus">
        <x-slot:actions>
            @if ($canCreate)
                <x-ui.button variant="primary" icon="plus" :href="route('losses.create')">Déclarer une perte</x-ui.button>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-3">
            <x-ui.stat-card
                label="Pertes du jour"
                :value="Format::money($todayValue)"
                :trend="Format::number($todayCount).' opération'.($todayCount > 1 ? 's' : '')"
                :trend-tone="$todayCount > 0 ? 'warning' : 'muted'"
                icon="trash-2"
            />
            <x-ui.stat-card label="Valeur du mois" :value="Format::money($monthValue)" icon="calendar" />
            <x-ui.stat-card
                label="Lots expirés à traiter"
                :value="Format::number($expiredLots)"
                :trend="$expiredLots > 0 ? 'à convertir en perte' : 'rien en attente'"
                :trend-tone="$expiredLots > 0 ? 'danger' : 'muted'"
                icon="package-x"
            />
        </div>

        <x-ui.card flush>
            <form method="GET" action="{{ route('losses.index') }}" class="flex flex-wrap items-end gap-3 p-4">
                <div class="min-w-56 flex-1">
                    <x-ui.search-input name="search" :value="$search" placeholder="Référence ou produit…" />
                </div>

                <div class="min-w-44">
                    <x-ui.select name="reason" label="Motif" :options="$reasonOptions" placeholder="— Tous —" :selected="$reason" />
                </div>

                <div class="min-w-44">
                    <x-ui.form-field name="date" label="Date" type="date" :value="$date" />
                </div>

                <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>

                @if ($search !== '' || $reason !== '' || $date !== '')
                    <x-ui.button variant="ghost" :href="route('losses.index')">Effacer</x-ui.button>
                @endif
            </form>

            @if ($losses->isEmpty())
                <div class="p-4">
                    <x-ui.empty-state
                        icon="trash-2"
                        title="{{ $search !== '' || $reason !== '' || $date !== '' ? 'Aucun résultat' : 'Aucune perte enregistrée' }}"
                        description="Les invendus expirés sont convertis automatiquement ; vous pouvez aussi déclarer une casse ou un défaut."
                    >
                        @if ($canCreate)
                            <x-slot:action>
                                <x-ui.button variant="primary" icon="plus" :href="route('losses.create')">Déclarer une perte</x-ui.button>
                            </x-slot:action>
                        @endif
                    </x-ui.empty-state>
                </div>
            @else
                <x-ui.data-table :headers="['Référence', 'Motif', 'Origine', 'Date', ['label' => 'Lignes', 'align' => 'right'], ['label' => 'Quantité', 'align' => 'right'], ['label' => 'Valeur', 'align' => 'right'], 'Auteur', '']">
                    @foreach ($losses as $loss)
                        <tr>
                            <td class="font-bold">{{ $loss->reference }}</td>
                            <td>{{ $loss->reason->label() }}</td>
                            <td>
                                <x-ui.status-badge
                                    :tone="$loss->origin->value === 'expiry' ? 'warning' : 'neutral'"
                                    :label="$loss->origin->label()"
                                    :icon="false"
                                />
                            </td>
                            <td>{{ $loss->occurred_on?->format('d/m/Y') ?? '—' }}</td>
                            <td class="numeric text-right">{{ Format::number($loss->items_count) }}</td>
                            <td class="numeric text-right">{{ Format::stock($loss->total_quantity) }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($loss->total_value) }}</td>
                            <td>{{ $loss->createdBy?->name ?? 'Système' }}</td>
                            <td>
                                <x-ui.button variant="secondary" size="sm" :href="route('losses.show', $loss)">Ouvrir</x-ui.button>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.data-table>

                <div class="p-4">
                    {{ $losses->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>
</x-layouts.app>
