@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Clients">
    <x-layout.page-header
        eyebrow="Commercial"
        title="Clients"
        description="Les clients du site : particuliers identifiés et acheteurs réguliers."
    >
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button variant="primary" icon="plus" :href="route('customers.create')">Nouveau client</x-ui.button>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card flush>
        <form method="GET" action="{{ route('customers.index') }}" class="flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-ui.search-input name="search" :value="$search" placeholder="Nom, téléphone, email…" />
            </div>
            <x-ui.button type="submit" variant="secondary">Rechercher</x-ui.button>
            @if ($search !== '')
                <x-ui.button variant="ghost" :href="route('customers.index')">Effacer</x-ui.button>
            @endif
        </form>

        @if ($customers->isEmpty())
            <div class="p-4">
                <x-ui.empty-state
                    icon="users"
                    title="{{ $search !== '' ? 'Aucun résultat' : 'Aucun client' }}"
                    description="Ajoutez un client pour suivre ses achats et son historique."
                >
                    @if ($canManage)
                        <x-slot:action>
                            <x-ui.button variant="primary" icon="plus" :href="route('customers.create')">Nouveau client</x-ui.button>
                        </x-slot:action>
                    @endif
                </x-ui.empty-state>
            </div>
        @else
            <x-ui.data-table :headers="['Client', 'Téléphone', 'Site', ['label' => 'Ventes', 'align' => 'right'], 'Statut', '']">
                @foreach ($customers as $customer)
                    <tr>
                        <td>
                            <span class="block font-bold text-ink">{{ $customer->name }}</span>
                            <span class="block text-[11px] text-soft">{{ $customer->email ?? '—' }}</span>
                        </td>
                        <td>{{ $customer->phone ?? '—' }}</td>
                        <td>{{ $customer->bakery?->name ?? '—' }}</td>
                        <td class="numeric text-right">{{ Format::number($customer->sales_count) }}</td>
                        <td>
                            <x-ui.status-badge
                                :tone="$customer->is_active ? 'success' : 'neutral'"
                                :label="$customer->is_active ? 'Actif' : 'Inactif'"
                                :icon="false"
                            />
                        </td>
                        <td>
                            @if ($canManage)
                                <x-ui.button variant="secondary" size="sm" :href="route('customers.edit', $customer)">Modifier</x-ui.button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>

            <div class="p-4">
                {{ $customers->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
