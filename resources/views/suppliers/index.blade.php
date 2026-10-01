@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Fournisseurs">
    <x-layout.page-header
        eyebrow="Achats"
        title="Fournisseurs"
        description="Le catalogue partagé des fournisseurs, leurs conditions et leur historique de commandes."
    >
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button variant="primary" icon="plus" :href="route('suppliers.create')">Nouveau fournisseur</x-ui.button>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card flush>
        <form method="GET" action="{{ route('suppliers.index') }}" class="flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-ui.search-input name="search" :value="$search" placeholder="Nom, contact, téléphone, spécialité…" />
            </div>

            <div class="min-w-44">
                <x-ui.select name="state" :options="$stateOptions" :selected="$state" placeholder="Tous les statuts" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>

            @if ($search !== '' || $state !== '')
                <x-ui.button variant="ghost" :href="route('suppliers.index')">Effacer</x-ui.button>
            @endif
        </form>

        @if ($suppliers->isEmpty())
            <div class="p-4">
                <x-ui.empty-state
                    icon="truck"
                    title="{{ $search !== '' || $state !== '' ? 'Aucun résultat' : 'Aucun fournisseur' }}"
                    description="Ajoutez un fournisseur pour lui adresser des commandes de matières premières."
                >
                    @if ($canManage)
                        <x-slot:action>
                            <x-ui.button variant="primary" icon="plus" :href="route('suppliers.create')">Nouveau fournisseur</x-ui.button>
                        </x-slot:action>
                    @endif
                </x-ui.empty-state>
            </div>
        @else
            <x-ui.data-table :headers="['Fournisseur', 'Contact', 'Spécialité', ['label' => 'Commandes', 'align' => 'right'], 'Statut', '']">
                @foreach ($suppliers as $supplier)
                    <tr>
                        <td>
                            <span class="block font-bold text-ink">{{ $supplier->name }}</span>
                            <span class="block text-[11px] text-soft">{{ $supplier->identification ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="block">{{ $supplier->contact_name ?? '—' }}</span>
                            <span class="block text-[11px] text-soft">{{ $supplier->phone ?? '—' }}</span>
                        </td>
                        <td>{{ $supplier->categories ?? '—' }}</td>
                        <td class="numeric text-right">{{ Format::number($supplier->purchase_orders_count) }}</td>
                        <td>
                            <x-ui.status-badge
                                :tone="$supplier->is_active ? 'success' : 'neutral'"
                                :label="$supplier->is_active ? 'Actif' : 'Inactif'"
                                :icon="false"
                            />
                        </td>
                        <td>
                            @if ($canManage)
                                <x-ui.button variant="secondary" size="sm" :href="route('suppliers.edit', $supplier)">Modifier</x-ui.button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>

            <div class="p-4">
                {{ $suppliers->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
