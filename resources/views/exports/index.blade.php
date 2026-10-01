<x-layouts.app title="Téléchargements">
    <x-layout.page-header
        eyebrow="Analyse & pilotage"
        title="Téléchargements"
        description="Les exports (rapports, grand livre, audit) sont produits en arrière-plan, puis restent téléchargeables 7 jours."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="chart-no-axes-combined" :href="route('reports.index')">Rapports</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" :title="session('status')" />
    @endif

    @php
        $hasPending = $exports->getCollection()->contains(fn ($export) => $export->status === 'pending');
    @endphp

    @if ($hasPending)
        {{-- Un export en attente : la page se relance seule jusqu'au résultat. --}}
        <div x-data x-init="setTimeout(() => window.location.reload(), 3000)"></div>
    @endif

    <x-ui.card flush>
        @if ($exports->isEmpty())
            <div class="p-4">
                <x-ui.empty-state
                    icon="download"
                    title="Aucun export"
                    description="Depuis un rapport, le grand livre ou l’audit, lancez un export CSV ou Excel : il apparaîtra ici."
                />
            </div>
        @else
            <x-ui.data-table :headers="['Libellé', 'Site', 'Format', 'Demandé le', 'Statut', 'Expire le', '']">
                @foreach ($exports as $export)
                    <tr>
                        <td>
                            <span class="block font-bold text-ink">{{ $export->label }}</span>
                            <span class="block text-[11px] text-soft">{{ $export->kind }}</span>
                        </td>
                        <td class="text-soft">{{ $export->bakery?->name ?? 'Tous les sites' }}</td>
                        <td class="uppercase">{{ $export->format }}</td>
                        <td class="whitespace-nowrap numeric">{{ $export->created_at?->format('d/m/Y H:i') }}</td>
                        <td>
                            @if ($export->status === 'ready')
                                <x-ui.status-badge tone="success" label="Prêt" />
                            @elseif ($export->status === 'failed')
                                <x-ui.status-badge tone="danger" label="Échec" />
                            @else
                                <x-ui.status-badge tone="warning" label="En préparation" />
                            @endif
                            @if ($export->status === 'failed' && $export->error)
                                <span class="block max-w-64 truncate text-[11px] text-danger" title="{{ $export->error }}">{{ $export->error }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap numeric text-soft">
                            {{ $export->expires_at?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="text-right">
                            @if ($export->isReady() && ! $export->isExpired())
                                <x-ui.button variant="secondary" size="sm" icon="download" :href="route('exports.download', $export)">Télécharger</x-ui.button>
                            @else
                                <span class="text-[11px] text-soft">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>

            <div class="p-4">
                {{ $exports->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
