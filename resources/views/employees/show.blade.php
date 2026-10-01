@php
    use App\Support\Format;
@endphp

<x-layouts.app title="{{ $employee->fullName() }}">
    <x-layout.page-header
        eyebrow="Employé"
        title="{{ $employee->fullName() }}"
        description="{{ $employee->positionLabel() }}"
    >
        <x-slot:actions>
            <x-ui.status-badge :tone="$employee->status->tone()" :label="$employee->status->label()" :icon="false" />

            <x-ui.button variant="secondary" :href="route('employees.edit', $employee)">Modifier</x-ui.button>
            <x-ui.button variant="primary" :href="route('payroll.index', ['year' => now()->year])">Paie</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.stat-card label="Salaire de base" :value="Format::money($employee->base_salary)" icon="banknote" />
            <x-ui.stat-card label="Contrat" :value="$employee->contract_type->label()" icon="file-text" :meta="$employee->hired_on?->format('d/m/Y')" />
            <x-ui.stat-card label="Bakery" :value="$employee->bakery?->name ?? '—'" icon="store" />
            <x-ui.stat-card label="Téléphone" :value="$employee->phone ?? '—'" icon="phone" />
        </div>

        <x-ui.card title="Fiche employé">
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="label-caps text-muted">Adresse</dt>
                    <dd class="mt-1 text-ink">{{ $employee->address ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="label-caps text-muted">Date d'embauche</dt>
                    <dd class="mt-1 text-ink">{{ $employee->hired_on?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="label-caps text-muted">Informations administratives</dt>
                    <dd class="mt-1 text-ink">{{ $employee->admin_note ?? '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Historique de paie" subtitle="Une ligne par période ; l'historique n'est jamais écrasé." flush>
            @if ($employee->payrollItems->isEmpty())
                <div class="p-4">
                    <x-ui.empty-state
                        icon="receipt"
                        title="Aucune paie"
                        description="Préparez une période de paie pour générer la première ligne de cet employé."
                    />
                </div>
            @else
                <x-ui.data-table :headers="['Période', 'Base', 'Primes', 'Avances', 'Retenues', ['label' => 'Net', 'align' => 'right'], 'Statut']">
                    @foreach ($employee->payrollItems as $item)
                        <tr>
                            <td>
                                <a href="{{ route('payroll.show', $item->period) }}" class="font-bold text-context-600 hover:underline">
                                    {{ $item->period?->monthLabel() ?? '—' }}
                                </a>
                            </td>
                            <td class="numeric">{{ Format::money($item->base_salary) }}</td>
                            <td class="numeric">{{ Format::money($item->bonuses) }}</td>
                            <td class="numeric">{{ Format::money($item->advances) }}</td>
                            <td class="numeric">{{ Format::money($item->deductions) }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($item->net_salary) }}</td>
                            <td>
                                @if ($item->period !== null)
                                    <x-ui.status-badge :tone="$item->period->status->tone()" :label="$item->period->status->label()" :icon="false" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            @endif
        </x-ui.card>

        <x-ui.card title="Versements" subtitle="Historique des paiements de salaire." flush>
            @if ($employee->payrollPayments->isEmpty())
                <div class="p-4">
                    <x-ui.empty-state icon="banknote" title="Aucun versement" description="Les versements apparaissent ici après validation et paiement d'une période." />
                </div>
            @else
                <x-ui.data-table :headers="['Période', 'Mode', 'Date', ['label' => 'Montant', 'align' => 'right']]">
                    @foreach ($employee->payrollPayments as $payment)
                        <tr>
                            <td class="font-bold">{{ $payment->item?->period?->monthLabel() ?? '—' }}</td>
                            <td>{{ $payment->methodLabel() }}</td>
                            <td>{{ $payment->paid_at?->format('d/m/Y') ?? '—' }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($payment->amount) }}</td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            @endif
        </x-ui.card>
    </div>
</x-layouts.app>
