@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Paie">
    <x-layout.page-header
        eyebrow="Ressources humaines"
        title="Paie"
        description="Les périodes de paie par site : salaire de base, primes, avances, retenues et net."
    >
        <x-slot:actions>
            <form method="GET" action="{{ route('payroll.index') }}" class="flex items-end gap-2">
                <div class="min-w-28">
                    <x-ui.form-field name="year" label="Exercice" type="number" min="2000" max="2100" :value="$year" />
                </div>
                <x-ui.button type="submit" variant="secondary">Afficher</x-ui.button>
            </form>
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
            <x-ui.stat-card label="Masse salariale {{ $year }}" :value="Format::money($totalNet)" icon="banknote" />
            <x-ui.stat-card label="Périodes {{ $year }}" :value="Format::number($periodCount)" icon="calendar" />
        </div>

        @if ($canManage)
            <x-ui.card title="Préparer une période" subtitle="La période est unique par site et par mois ; ses lignes sont générées depuis les employés actifs.">
                <form method="POST" action="{{ route('payroll.periods.store') }}" class="grid grid-cols-1 items-end gap-3 sm:grid-cols-4">
                    @csrf

                    <x-ui.form-field name="period_year" label="Exercice" type="number" min="2000" max="2100" :value="$year" required />
                    <x-ui.select
                        name="period_month"
                        label="Mois"
                        :options="[
                            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
                            7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
                        ]"
                        :selected="now()->month"
                        required
                    />
                    <x-ui.button type="submit" variant="primary" icon="calendar-plus">Préparer</x-ui.button>
                </form>
            </x-ui.card>
        @endif

        <x-ui.card title="Périodes {{ $year }}" flush>
            @if ($periods->isEmpty())
                <div class="p-4">
                    <x-ui.empty-state
                        icon="wallet"
                        title="Aucune période"
                        description="Préparez une période de paie pour générer les lignes depuis les employés actifs."
                    />
                </div>
            @else
                <x-ui.data-table :headers="['Période', 'Du / au', ['label' => 'Employés', 'align' => 'right'], ['label' => 'Net', 'align' => 'right'], 'Statut', '']">
                    @foreach ($periods as $period)
                        <tr>
                            <td class="font-bold">{{ $period->monthLabel() }}</td>
                            <td>
                                {{ $period->started_on?->format('d/m/Y') ?? '—' }}
                                @if ($period->ended_on !== null)
                                    → {{ $period->ended_on->format('d/m/Y') }}
                                @endif
                            </td>
                            <td class="numeric text-right">{{ Format::number($period->items_count) }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($period->total_net) }}</td>
                            <td>
                                <x-ui.status-badge :tone="$period->status->tone()" :label="$period->status->label()" :icon="false" />
                            </td>
                            <td>
                                <x-ui.button variant="secondary" size="sm" :href="route('payroll.show', $period)">Ouvrir</x-ui.button>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.data-table>

                <div class="p-4">
                    {{ $periods->links() }}
                </div>
            @endif
        </x-ui.card>
    </div>
</x-layouts.app>
