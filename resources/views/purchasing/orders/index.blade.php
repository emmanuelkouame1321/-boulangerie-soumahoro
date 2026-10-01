@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Achats & réceptions">
    <x-layout.page-header
        eyebrow="Approvisionnement"
        title="Achats & réceptions"
        description="Les commandes de matières premières. Une réception validée entre le stock automatiquement, une seule fois."
    >
        <x-slot:actions>
            @if ($canCreate)
                <x-ui.button variant="primary" icon="plus" :href="route('purchasing.orders.create')">Nouvelle commande</x-ui.button>
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
        <form method="GET" action="{{ route('purchasing.orders.index') }}" class="flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-ui.search-input name="search" :value="$search" placeholder="Référence ou fournisseur…" />
            </div>

            <div class="min-w-48">
                <x-ui.select name="status" :options="$statusOptions" :selected="$status" placeholder="Tous les statuts" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>

            @if ($search !== '' || $status !== '')
                <x-ui.button variant="ghost" :href="route('purchasing.orders.index')">Effacer</x-ui.button>
            @endif
        </form>

        @if ($orders->isEmpty())
            <div class="p-4">
                <x-ui.empty-state
                    icon="shopping-cart"
                    title="{{ $search !== '' || $status !== '' ? 'Aucun résultat' : 'Aucune commande' }}"
                    description="Créez une commande fournisseur, puis enregistrez sa réception à la livraison."
                >
                    @if ($canCreate)
                        <x-slot:action>
                            <x-ui.button variant="primary" icon="plus" :href="route('purchasing.orders.create')">Nouvelle commande</x-ui.button>
                        </x-slot:action>
                    @endif
                </x-ui.empty-state>
            </div>
        @else
            <x-ui.data-table :headers="['Commande', 'Fournisseur', 'Site', 'Date', ['label' => 'Lignes', 'align' => 'right'], ['label' => 'Total', 'align' => 'right'], 'Statut', '']">
                @foreach ($orders as $order)
                    <tr>
                        <td class="font-bold">{{ $order->reference }}</td>
                        <td>{{ $order->supplier?->name ?? '—' }}</td>
                        <td>{{ $order->bakery?->name ?? '—' }}</td>
                        <td>{{ $order->ordered_at?->format('d/m/Y') ?? '—' }}</td>
                        <td class="numeric text-right">{{ Format::number($order->items_count) }}</td>
                        <td class="numeric text-right font-bold">{{ Format::money($order->total) }}</td>
                        <td>
                            <x-ui.status-badge :tone="$order->status->tone()" :label="$order->status->label()" :icon="false" />
                        </td>
                        <td>
                            <x-ui.button variant="secondary" size="sm" :href="route('purchasing.orders.show', $order)">Ouvrir</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>

            <div class="p-4">
                {{ $orders->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
