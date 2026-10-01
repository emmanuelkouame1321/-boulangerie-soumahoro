@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Paie {{ $period->monthLabel() }}">
    <x-layout.page-header
        eyebrow="Paie"
        title="{{ $period->monthLabel() }}"
        description="Référence {{ $period->reference }} — du {{ $period->started_on?->format('d/m/Y') }} au {{ $period->ended_on?->format('d/m/Y') }}."
    >
        <x-slot:actions>
            <x-ui.status-badge :tone="$period->status->tone()" :label="$period->status->label()" :icon="false" />

            @if ($canManage && $period->status->value === 'draft')
                <form method="POST" action="{{ route('payroll.generate', $period) }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">Générer les lignes</x-ui.button>
                </form>

                <form method="POST" action="{{ route('payroll.validate', $period) }}">
                    @csrf
                    <x-ui.button type="submit" variant="primary">Valider</x-ui.button>
                </form>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    @if ($period->validated_at !== null)
        <x-ui.alert tone="info" class="mb-4">
            Période validée par {{ $period->validatedBy?->name ?? '—' }}
            le {{ $period->validated_at->format('d/m/Y H:i') }}.
            @if ($period->paid_at !== null)
                Versée le {{ $period->paid_at->format('d/m/Y H:i') }} par {{ $period->paidBy?->name ?? '—' }}.
            @endif
        </x-ui.alert>
    @endif

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-5">
            <x-ui.stat-card label="Base" :value="Format::money($period->total_base)" />
            <x-ui.stat-card label="Primes" :value="Format::money($period->total_bonuses)" />
            <x-ui.stat-card label="Avances" :value="Format::money($period->total_advances)" />
            <x-ui.stat-card label="Retenues" :value="Format::money($period->total_deductions)" />
            <x-ui.stat-card label="Net" :value="Format::money($period->total_net)" />
        </div>

        @if ($canManage && $period->status->value === 'validated')
            <x-ui.card title="Verser les salaires" subtitle="Une fois versée, la période est figée.">
                <form method="POST" action="{{ route('payroll.pay', $period) }}" class="grid grid-cols-1 items-end gap-3 sm:grid-cols-4">
                    @csrf

                    <x-ui.select name="method" label="Mode de paiement" :options="$paymentMethods" required />
                    <x-ui.form-field name="paid_at" label="Date de paiement" type="date" :value="now()->format('Y-m-d')" required />
                    <x-ui.form-field name="note" label="Note" />
                    <x-ui.button type="submit" variant="primary" icon="banknote">Verser</x-ui.button>
                </form>
            </x-ui.card>
        @endif

        <x-ui.card title="Lignes de paie" subtitle="Le net vaut base + primes − avances − retenues." flush>
            @if ($period->items->isEmpty())
                <div class="p-4">
                    <x-ui.empty-state
                        icon="users"
                        title="Aucune ligne"
                        description="Générez les lignes depuis les employés actifs du site pour préparer la paie."
                    />
                </div>
            @else
                <x-ui.data-table :headers="['Employé', ['label' => 'Base', 'align' => 'right'], ['label' => 'Primes', 'align' => 'right'], ['label' => 'Avances', 'align' => 'right'], ['label' => 'Retenues', 'align' => 'right'], ['label' => 'Net', 'align' => 'right'], '']">
                    @foreach ($period->items as $item)
                        <tr>
                            <td>
                                @if ($item->employee !== null)
                                    <a href="{{ route('employees.show', $item->employee) }}" class="font-bold text-context-600 hover:underline">
                                        {{ $item->employee->fullName() }}
                                    </a>
                                @else
                                    <span class="font-bold">—</span>
                                @endif
                            </td>
                            <td class="numeric text-right">{{ Format::money($item->base_salary) }}</td>
                            <td class="numeric text-right">{{ Format::money($item->bonuses) }}</td>
                            <td class="numeric text-right">{{ Format::money($item->advances) }}</td>
                            <td class="numeric text-right">{{ Format::money($item->deductions) }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($item->net_salary) }}</td>
                            <td>
                                @if ($canManage && ! $period->isLocked())
                                    <form method="POST" action="{{ route('payroll.items.update', $item) }}" class="flex flex-wrap items-end justify-end gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="base_salary" value="{{ $item->base_salary }}">
                                        <div class="w-24">
                                            <x-ui.form-field name="bonuses" label="Primes" type="number" min="0" :value="$item->bonuses" :id="'bonuses-'.$item->getKey()" />
                                        </div>
                                        <div class="w-24">
                                            <x-ui.form-field name="advances" label="Avances" type="number" min="0" :value="$item->advances" :id="'advances-'.$item->getKey()" />
                                        </div>
                                        <div class="w-24">
                                            <x-ui.form-field name="deductions" label="Retenues" type="number" min="0" :value="$item->deductions" :id="'deductions-'.$item->getKey()" />
                                        </div>
                                        <x-ui.button type="submit" variant="secondary" size="sm">Ajuster</x-ui.button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            @endif
        </x-ui.card>

        @if ($period->payments->isNotEmpty())
            <x-ui.card title="Versements" subtitle="Historique des paiements de cette période." flush>
                <x-ui.data-table :headers="['Employé', 'Mode', 'Date', ['label' => 'Montant', 'align' => 'right']]">
                    @foreach ($period->payments as $payment)
                        <tr>
                            <td class="font-bold">{{ $payment->employee?->fullName() ?? '—' }}</td>
                            <td>{{ $payment->methodLabel() }}</td>
                            <td>{{ $payment->paid_at?->format('d/m/Y') ?? '—' }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($payment->amount) }}</td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            </x-ui.card>
        @endif
    </div>
</x-layouts.app>
