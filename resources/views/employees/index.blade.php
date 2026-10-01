@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Employés">
    <x-layout.page-header
        eyebrow="Ressources humaines"
        title="Employés"
        description="Les employés du site : poste, contrat, salaire de base et historique de paie."
    >
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button variant="primary" icon="plus" :href="route('employees.create')">Nouvel employé</x-ui.button>
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
        <x-ui.stat-card label="Employés actifs" :value="Format::number($activeCount)" icon="users" />
    </div>

    <x-ui.card flush>
        <form method="GET" action="{{ route('employees.index') }}" class="flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-ui.search-input name="search" :value="$search" placeholder="Nom, téléphone, poste…" />
            </div>

            <div class="min-w-40">
                <x-ui.select name="status" label="Statut" :options="$statusOptions" :selected="$status" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>

            @if ($search !== '' || $status !== '')
                <x-ui.button variant="ghost" :href="route('employees.index')">Effacer</x-ui.button>
            @endif
        </form>

        @if ($employees->isEmpty())
            <div class="p-4">
                <x-ui.empty-state
                    icon="users"
                    title="{{ $search !== '' || $status !== '' ? 'Aucun résultat' : 'Aucun employé' }}"
                    description="Ajoutez un employé pour préparer la paie et suivre son historique."
                >
                    @if ($canManage)
                        <x-slot:action>
                            <x-ui.button variant="primary" icon="plus" :href="route('employees.create')">Nouvel employé</x-ui.button>
                        </x-slot:action>
                    @endif
                </x-ui.empty-state>
            </div>
        @else
            <x-ui.data-table :headers="['Employé', 'Poste', 'Contrat', ['label' => 'Salaire', 'align' => 'right'], 'Statut', '']">
                @foreach ($employees as $employee)
                    <tr>
                        <td>
                            <span class="block font-bold text-ink">{{ $employee->fullName() }}</span>
                            <span class="block text-[11px] text-soft">{{ $employee->phone ?? '—' }}</span>
                        </td>
                        <td>{{ $employee->positionLabel() }}</td>
                        <td>{{ $employee->contract_type->label() }}</td>
                        <td class="numeric text-right">{{ Format::money($employee->base_salary) }}</td>
                        <td>
                            <x-ui.status-badge :tone="$employee->status->tone()" :label="$employee->status->label()" :icon="false" />
                        </td>
                        <td>
                            <x-ui.button variant="secondary" size="sm" :href="route('employees.show', $employee)">Ouvrir</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>

            <div class="p-4">
                {{ $employees->links() }}
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
