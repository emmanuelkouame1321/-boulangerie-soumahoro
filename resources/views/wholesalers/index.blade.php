@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Grossistes">
    <x-layout.page-header
        eyebrow="Commercial"
        title="Grossistes"
        description="Les fiches grossistes du site : contacts, grille de prix, conditions de paiement et historique de ristournes."
    >
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button variant="primary" icon="plus" :href="route('wholesalers.create')">Nouveau grossiste</x-ui.button>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    <div class="mb-4 grid grid-cols-1 gap-3.5 md:grid-cols-3">
        <x-ui.stat-card label="Grossistes actifs" :value="Format::number($activeCount)" icon="store" />
    </div>

    <x-ui.card flush>
        <form method="GET" action="{{ route('wholesalers.index') }}" class="flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-ui.search-input name="search" :value="$search" placeholder="Nom, contact, zone…" />
            </div>

            <div class="min-w-40">
                <x-ui.select name="active" :options="['' => 'Tous', '1' => 'Actifs', '0' => 'Inactifs']" :selected="$active" label="Statut" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>

            @if ($search !== '' || $active !== '')
                <x-ui.button variant="ghost" :href="route('wholesalers.index')">Effacer</x-ui.button>
            @endif
        </form>

        @if ($wholesalers->isEmpty())
            <div class="p-4">
                <x-ui.empty-state
                    icon="store"
                    title="{{ $search !== '' || $active !== '' ? 'Aucun résultat' : 'Aucun grossiste' }}"
                    description="Ajoutez une fiche grossiste pour suivre ses achats, sa régularité et sa ristourne."
                >
                    @if ($canManage)
                        <x-slot:action>
                            <x-ui.button variant="primary" icon="plus" :href="route('wholesalers.create')">Nouveau grossiste</x-ui.button>
                        </x-slot:action>
                    @endif
                </x-ui.empty-state>
            </div>
        @else
            <x-ui.data-table :headers="['Grossiste', 'Contact', 'Zone', 'Prix', 'Statut', '']">
                @foreach ($wholesalers as $wholesaler)
                    <tr>
                        <td>
                            <span class="block font-bold text-ink">{{ $wholesaler->name }}</span>
                            <span class="block text-[11px] text-soft">{{ $wholesaler->bakery?->name ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="block">{{ $wholesaler->contact_name ?? '—' }}</span>
                            <span class="block text-[11px] text-soft">{{ $wholesaler->phone ?? '—' }}</span>
                        </td>
                        <td>{{ $wholesaler->zone ?? '—' }}</td>
                        <td>{{ $wholesaler->priceModeLabel() }}</td>
                        <td>
                            <x-ui.status-badge
                                :tone="$wholesaler->is_active ? 'success' : 'neutral'"
                                :label="$wholesaler->is_active ? 'Actif' : 'Inactif'"
                                :icon="false"
                            />
                        </td>
                        <td>
                            <x-ui.button variant="secondary" size="sm" :href="route('wholesalers.show', $wholesaler)">Ouvrir</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>

            <div class="p-4">
                {{ $wholesalers->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
