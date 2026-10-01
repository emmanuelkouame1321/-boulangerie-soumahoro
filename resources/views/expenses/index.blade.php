@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Charges">
    <x-layout.page-header
        eyebrow="Dépenses d'exploitation"
        title="Charges"
        description="Les charges du site par catégorie : électricité, eau, loyer, carburant et autres."
    >
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button variant="primary" icon="plus" :href="route('expenses.create')">Ajouter une charge</x-ui.button>
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
        <div class="grid grid-cols-1 gap-3.5 md:grid-cols-3">
            <x-ui.stat-card label="Période sélectionnée" :value="Format::money($total)" icon="wallet" />
            <x-ui.stat-card label="Mois en cours" :value="Format::money($monthTotal)" icon="calendar" />
        </div>

        <x-ui.card flush>
            <form method="GET" action="{{ route('expenses.index') }}" class="flex flex-wrap items-end gap-3 p-4">
                <div class="min-w-48 flex-1">
                    <x-ui.search-input name="search" :value="$search" placeholder="Référence, objet, bénéficiaire…" />
                </div>
                <div class="min-w-36">
                    <x-ui.form-field name="from" label="Du" type="date" :value="$from" />
                </div>
                <div class="min-w-36">
                    <x-ui.form-field name="to" label="Au" type="date" :value="$to" />
                </div>
                <div class="min-w-40">
                    <x-ui.select name="category" label="Catégorie" :options="$categoryOptions" :selected="$category" />
                </div>
                <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>
            </form>

            @if ($expenses->isEmpty())
                <div class="p-4">
                    <x-ui.empty-state
                        icon="wallet"
                        title="{{ $search !== '' || $category !== '' ? 'Aucun résultat' : 'Aucune charge' }}"
                        description="Enregistrez une charge pour suivre les dépenses d'exploitation du site."
                    >
                        @if ($canManage)
                            <x-slot:action>
                                <x-ui.button variant="primary" icon="plus" :href="route('expenses.create')">Ajouter une charge</x-ui.button>
                            </x-slot:action>
                        @endif
                    </x-ui.empty-state>
                </div>
            @else
                <x-ui.data-table :headers="['Date', 'Catégorie', 'Objet', 'Site', 'Bénéficiaire', 'Paiement', ['label' => 'Montant', 'align' => 'right'], '']">
                    @foreach ($expenses as $expense)
                        <tr>
                            <td>{{ $expense->spent_on?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $expense->category?->name ?? 'Non classé' }}</td>
                            <td>{{ $expense->label ?? '—' }}</td>
                            <td>{{ $expense->bakery?->name ?? '—' }}</td>
                            <td>{{ $expense->payee ?? '—' }}</td>
                            <td>{{ $expense->methodLabel() }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($expense->amount) }}</td>
                            <td>
                                @if ($canManage)
                                    <x-ui.button variant="secondary" size="sm" :href="route('expenses.edit', $expense)">Modifier</x-ui.button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.data-table>

                <div class="p-4">
                    {{ $expenses->links() }}
                </div>
            @endif
        </x-ui.card>

        <x-ui.card title="Catégories de charges" subtitle="Administrables : une catégorie se désactive, ne se supprime pas." flush>
            @if ($canManage)
                <form method="POST" action="{{ route('expenses.categories.store') }}" class="grid grid-cols-1 items-end gap-3 border-b border-line p-4 sm:grid-cols-4">
                    @csrf
                    <x-ui.form-field name="code" label="Code" placeholder="ex. securite" required />
                    <x-ui.form-field name="name" label="Libellé" placeholder="ex. Sécurité" required />
                    <x-ui.form-field name="position" label="Ordre" type="number" min="0" :value="0" />
                    <x-ui.button type="submit" variant="primary" icon="plus">Ajouter</x-ui.button>
                </form>
            @endif

            <x-ui.data-table :headers="['Catégorie', 'Code', ['label' => 'Charges', 'align' => 'right'], 'Statut', '']">
                @foreach ($categories as $category)
                    <tr>
                        <td class="font-bold">{{ $category->name }}</td>
                        <td>{{ $category->code }}</td>
                        <td class="numeric text-right">{{ Format::number($category->expenses_count) }}</td>
                        <td>
                            <x-ui.status-badge
                                :tone="$category->is_active ? 'success' : 'neutral'"
                                :label="$category->is_active ? 'Active' : 'Inactive'"
                                :icon="false"
                            />
                        </td>
                        <td>
                            @if ($canManage && $category->is_active)
                                <form method="POST" action="{{ route('expenses.categories.deactivate', $category) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="ghost" size="sm">Désactiver</x-ui.button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </x-ui.card>
    </div>
</x-layouts.app>
