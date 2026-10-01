@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Production">
    <x-layout.page-header
        eyebrow="Opérations"
        title="Production"
        description="Les ordres de production. Une production validée consomme les matières, entre le produit fini et crée un lot — une seule fois."
    >
        <x-slot:actions>
            @if ($canCreate)
                <x-ui.button variant="secondary" icon="clipboard-list" :href="route('production.recipes.index')">Recettes</x-ui.button>
                <x-ui.button variant="primary" icon="plus" :href="route('production.create')">Nouvelle production</x-ui.button>
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
        <form method="GET" action="{{ route('production.index') }}" class="flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-ui.search-input name="search" :value="$search" placeholder="Référence ou recette…" />
            </div>

            <div class="min-w-48">
                <x-ui.select name="status" :options="$statusOptions" :selected="$status" placeholder="Tous les statuts" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>

            @if ($search !== '' || $status !== '')
                <x-ui.button variant="ghost" :href="route('production.index')">Effacer</x-ui.button>
            @endif
        </form>

        @if ($orders->isEmpty())
            <div class="p-4">
                <x-ui.empty-state
                    icon="factory"
                    title="{{ $search !== '' || $status !== '' ? 'Aucun résultat' : 'Aucune production' }}"
                    description="Lancez une production depuis une recette : les matières nécessaires sont calculées automatiquement."
                >
                    @if ($canCreate)
                        <x-slot:action>
                            <x-ui.button variant="primary" icon="plus" :href="route('production.create')">Nouvelle production</x-ui.button>
                        </x-slot:action>
                    @endif
                </x-ui.empty-state>
            </div>
        @else
            <x-ui.data-table :headers="['Ordre', 'Produit', 'Site', 'Date', ['label' => 'Théorique', 'align' => 'right'], ['label' => 'Réel', 'align' => 'right'], ['label' => 'Écart', 'align' => 'right'], 'Statut', '']">
                @foreach ($orders as $order)
                    <tr>
                        <td class="font-bold">{{ $order->reference }}</td>
                        <td>
                            <span class="font-medium">{{ $order->recipe?->product?->name ?? '—' }}</span>
                            <span class="block text-[11px] text-soft">{{ $order->recipe?->name ?? '' }}</span>
                        </td>
                        <td>{{ $order->bakery?->name ?? '—' }}</td>
                        <td>{{ $order->produced_on?->format('d/m/Y') ?? '—' }}</td>
                        <td class="numeric text-right">{{ Format::stock($order->theoretical_quantity) }}</td>
                        <td class="numeric text-right">{{ $order->actual_quantity !== null ? Format::stock($order->actual_quantity) : '—' }}</td>
                        <td class="numeric text-right {{ $order->hasGap() ? 'font-bold text-warning' : 'text-muted' }}">
                            {{ $order->gap !== null ? Format::signed($order->gap) : '—' }}
                        </td>
                        <td>
                            <x-ui.status-badge :tone="$order->status->tone()" :label="$order->status->label()" :icon="false" />
                        </td>
                        <td>
                            <x-ui.button variant="secondary" size="sm" :href="route('production.show', $order)">Ouvrir</x-ui.button>
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
