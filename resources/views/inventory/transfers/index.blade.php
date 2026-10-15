@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Transferts inter-sites">
    <x-layout.page-header
        eyebrow="Logistique"
        title="Transferts inter-sites"
        description="Mouvements de stock d'une boulangerie vers une autre : sortie chez l'expéditeur, entrée chez le destinataire."
    >
        <x-slot:actions>
            @if ($canCreate)
                <x-ui.button variant="primary" icon="plus" :href="route('inventory.transfers.create')">Nouveau transfert</x-ui.button>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    <x-ui.card flush>
        <form method="GET" action="{{ route('inventory.transfers.index') }}" class="flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-ui.search-input name="search" :value="$search" placeholder="Référence ou produit…" />
            </div>

            <div class="min-w-44">
                <x-ui.select name="status" label="Statut" :options="$statusOptions" placeholder="— Tous —" :selected="$status" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>

            @if ($search !== '' || $status !== '')
                <x-ui.button variant="ghost" :href="route('inventory.transfers.index')">Effacer</x-ui.button>
            @endif
        </form>

        @if ($transfers->isEmpty())
            <div class="p-4">
                <x-ui.empty-state
                    icon="truck"
                    title="{{ $search !== '' || $status !== '' ? 'Aucun résultat' : 'Aucun transfert' }}"
                    description="Les transferts relient deux boulangeries : le stock sort de l'une et entre dans l'autre, en deux temps."
                >
                    @if ($canCreate)
                        <x-slot:action>
                            <x-ui.button variant="primary" icon="plus" :href="route('inventory.transfers.create')">Nouveau transfert</x-ui.button>
                        </x-slot:action>
                    @endif
                </x-ui.empty-state>
            </div>
        @else
            <x-ui.data-table :headers="['Référence', 'Produit', 'Trajet', 'Date', 'Statut', ['label' => 'Quantité', 'align' => 'right'], '']">
                @foreach ($transfers as $transfer)
                    <tr>
                        <td class="font-bold">{{ $transfer->reference }}</td>
                        <td>{{ $transfer->product?->name ?? '—' }}</td>
                        <td>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="font-medium">{{ $transfer->sourceBakery?->name ?? '—' }}</span>
                                <x-ui.icon name="arrow-right" class="h-3.5 w-3.5 text-soft" />
                                <span class="font-medium">{{ $transfer->destinationBakery?->name ?? '—' }}</span>
                            </span>
                        </td>
                        <td>{{ $transfer->transferred_on?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <x-ui.status-badge :tone="$transfer->status->tone()" :label="$transfer->status->label()" :icon="false" />
                        </td>
                        <td class="numeric text-right">{{ Format::stock($transfer->quantity) }}</td>
                        <td>
                            <x-ui.button variant="secondary" size="sm" :href="route('inventory.transfers.show', $transfer)">Ouvrir</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>

            <div class="p-4">
                {{ $transfers->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
