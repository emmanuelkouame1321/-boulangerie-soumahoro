@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Pertes & invendus">
    <x-layout.page-header eyebrow="Contrôle des écarts" title="Pertes & invendus">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" wire:click="$dispatch('open-modal', 'loss-create')">Déclarer une perte</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
            <x-ui.stat-card label="Pertes du jour" :value="Format::money(85_000)" trend="32 opérations" trend-tone="warning" icon="trash-2" />
            <x-ui.stat-card label="Invendus" :value="Format::number(124)" unit="unités" trend="à écouler" trend-tone="warning" icon="package-x" />
            <x-ui.stat-card label="Expirés" :value="Format::number(42)" trend="à traiter" trend-tone="danger" icon="calendar-x" />
            <x-ui.stat-card label="Écarts" :value="Format::number(8)" trend="justifications" trend-tone="warning" icon="triangle-alert" />
        </div>

        <x-ui.card flush>
            <x-ui.data-table :headers="['Référence', 'Produit / lot', ['label' => 'Quantité', 'align' => 'right'], 'Motif', ['label' => 'Valeur', 'align' => 'right'], 'Utilisateur', 'Statut']">
                @foreach ([
                    ['LOSS-0042', 'Croissant • #20260930-002', 15, 'Péremption', 12_000, 'Awa', 'success', 'Validée'],
                    ['LOSS-0041', 'Baguette • #20261001-001', 8, 'Défaut production', 4_000, 'Kouassi', 'warning', 'À justifier'],
                    ['LOSS-0040', 'Cake nature • #20260929-004', 3, 'Invendu', 10_500, 'Awa', 'success', 'Validée'],
                ] as [$ref, $product, $qty, $reason, $value, $user, $tone, $status])
                    <tr>
                        <td class="font-bold">{{ $ref }}</td>
                        <td>{{ $product }}</td>
                        <td class="numeric text-right">{{ $qty }}</td>
                        <td>{{ $reason }}</td>
                        <td class="numeric text-right font-bold">{{ Format::money($value) }}</td>
                        <td>{{ $user }}</td>
                        <td><x-ui.status-badge :tone="$tone" :label="$status" :icon="false" /></td>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </x-ui.card>
    </div>

    <x-ui.modal name="loss-create" title="Déclarer une perte" subtitle="Formulaire prototype">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            <x-ui.form-field name="product" label="Produit / lot" placeholder="Saisir…" />
            <x-ui.form-field name="quantity" label="Quantité" type="number" inputmode="numeric" />
            <x-ui.select name="reason" :options="['Péremption' => 'Péremption', 'Invendu' => 'Invendu', 'Défaut production' => 'Défaut production', 'Casse' => 'Casse']" label="Motif" />
            <x-ui.form-field name="comment" label="Commentaire" placeholder="Optionnel" />
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" @click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" @click="open = false; window.toast('Perte enregistrée')">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</x-layouts.app>
