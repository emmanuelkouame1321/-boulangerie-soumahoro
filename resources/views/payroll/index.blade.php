@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Paie">
    <x-layout.page-header eyebrow="Ressources humaines" title="Paie">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" wire:click="$dispatch('open-modal', 'payroll-create')">Préparer la paie</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
            <x-ui.stat-card label="Masse salariale" :value="Format::money(8_450_000)" trend="Septembre 2026" trend-tone="success" icon="wallet-cards" />
            <x-ui.stat-card label="Employés" :value="Format::number(34)" trend="3 sites" trend-tone="success" icon="users" />
            <x-ui.stat-card label="Avances" :value="Format::money(620_000)" trend="7 employés" trend-tone="warning" icon="arrow-down-to-line" />
            <x-ui.stat-card label="À payer" :value="Format::money(8_450_000)" trend="30/09/2026" trend-tone="warning" icon="calendar-clock" />
        </div>

        <x-ui.card flush>
            <x-ui.data-table :headers="['Employé', 'Poste', ['label' => 'Base', 'align' => 'right'], ['label' => 'Primes', 'align' => 'right'], ['label' => 'Retenues', 'align' => 'right'], ['label' => 'Net', 'align' => 'right'], 'Statut']">
                @foreach ([
                    ['Kouassi Jean', 'Responsable production', 420_000, 40_000, 0, 460_000, 'warning', 'À payer'],
                    ['Awa Kouassi', 'Caissière', 280_000, 20_000, 30_000, 270_000, 'warning', 'À payer'],
                    ['Mariam Yao', 'Responsable vente', 390_000, 50_000, 0, 440_000, 'success', 'Payée'],
                ] as [$name, $role, $base, $bonus, $deduction, $net, $tone, $status])
                    <tr>
                        <td class="font-bold">{{ $name }}</td>
                        <td>{{ $role }}</td>
                        <td class="numeric text-right">{{ Format::money($base, false) }}</td>
                        <td class="numeric text-right">{{ Format::money($bonus, false) }}</td>
                        <td class="numeric text-right">{{ Format::money($deduction, false) }}</td>
                        <td class="numeric text-right font-bold">{{ Format::money($net) }}</td>
                        <td><x-ui.status-badge :tone="$tone" :label="$status" :icon="false" /></td>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </x-ui.card>
    </div>

    <x-ui.modal name="payroll-create" title="Nouvelle période de paie" subtitle="Formulaire prototype">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            <x-ui.form-field name="period" label="Période" placeholder="Septembre 2026" />
            <x-ui.select name="site" :options="['Tous les sites' => 'Tous les sites', 'Boulangerie Centre' => 'Boulangerie Centre']" label="Site" />
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" @click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" @click="open = false; window.toast('Période de paie créée')">Créer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</x-layouts.app>
