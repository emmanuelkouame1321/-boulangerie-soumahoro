@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Ristournes">
    <x-layout.page-header eyebrow="Grossistes • exercice 2026" title="Ristournes">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" wire:click="$dispatch('open-modal', 'rebate-create')">Configurer</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <x-ui.card title="Paliers configurés" subtitle="Exemple de configuration — administrable">
            <x-slot:actions>
                <x-ui.status-badge tone="info" label="2026" :icon="false" />
            </x-slot:actions>

            <x-ui.data-table :headers="['Seuil annuel', 'Taux', 'Critère', 'État']">
                @foreach ([
                    ['0 — 50 000', '0 %', 'Quantité', 'neutral'],
                    ['50 001 — 100 000', '1 %', 'Quantité', 'success'],
                    ['100 001 — 200 000', '2 %', 'Quantité', 'success'],
                    ['> 200 000', '3 %', 'Quantité', 'success'],
                ] as [$threshold, $rate, $criterion, $tone])
                    <tr>
                        <td>{{ $threshold }}</td>
                        <td class="font-bold">{{ $rate }}</td>
                        <td>{{ $criterion }}</td>
                        <td><x-ui.status-badge :tone="$tone" label="Actif" :icon="false" /></td>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </x-ui.card>

        <x-ui.card title="Calcul annuel">
            <x-slot:actions>
                <x-ui.button variant="secondary" size="sm" icon="download" onclick="window.toast('Export en préparation')">Exporter</x-ui.button>
            </x-slot:actions>

            <x-ui.data-table :headers="['Grossiste', ['label' => 'Quantité', 'align' => 'right'], ['label' => 'CA éligible', 'align' => 'right'], 'Palier', ['label' => 'Ristourne théorique', 'align' => 'right'], 'Validation']">
                @foreach ([
                    ['Boulangerie ABC', 82_400, 41_200_000, '1 %', 412_000, 'warning', 'À valider'],
                    ['Hôtel XYZ', 54_200, 32_800_000, '1 %', 328_000, 'success', 'Validée'],
                ] as [$name, $qty, $revenue, $tier, $rebate, $tone, $status])
                    <tr>
                        <td class="font-bold">{{ $name }}</td>
                        <td class="numeric text-right">{{ Format::number($qty) }}</td>
                        <td class="numeric text-right">{{ Format::money($revenue) }}</td>
                        <td>{{ $tier }}</td>
                        <td class="numeric text-right font-bold">{{ Format::money($rebate) }}</td>
                        <td><x-ui.status-badge :tone="$tone" :label="$status" :icon="false" /></td>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </x-ui.card>
    </div>

    <x-ui.modal name="rebate-create" title="Nouvelle règle de ristourne" subtitle="Formulaire prototype">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            <x-ui.form-field name="threshold" label="Seuil annuel" type="number" inputmode="numeric" />
            <x-ui.form-field name="rate" label="Taux (%)" type="number" inputmode="decimal" />
            <x-ui.select name="criterion" :options="['Quantité' => 'Quantité', 'Chiffre d’affaires' => 'Chiffre d’affaires', 'Famille' => 'Famille']" label="Critère" />
            <x-ui.select name="state" :options="['Actif' => 'Actif', 'Inactif' => 'Inactif']" label="État" />
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" @click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" @click="open = false; window.toast('Règle enregistrée')">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</x-layouts.app>
