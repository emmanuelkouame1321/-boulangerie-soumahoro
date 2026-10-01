@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Achats & réceptions">
    <x-layout.page-header eyebrow="Approvisionnement" title="Achats & réceptions">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" wire:click="$dispatch('open-modal', 'purchase-create')">Nouvelle commande</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card flush>
        <div class="flex flex-wrap gap-2 p-4">
            <x-ui.search-input placeholder="Commande, fournisseur…" />
            <x-ui.select name="status" :options="['En attente' => 'En attente', 'Commandée' => 'Commandée', 'Partiellement livrée' => 'Partiellement livrée', 'Livrée' => 'Livrée', 'Validée' => 'Validée']" placeholder="Tous les statuts" class="min-w-48" />
            <x-ui.button variant="secondary">Réceptions à contrôler <x-ui.status-badge tone="warning" label="3" :icon="false" /></x-ui.button>
        </div>

        <x-ui.data-table :headers="['Commande', 'Fournisseur', 'Site', 'Date', ['label' => 'Total', 'align' => 'right'], 'Statut', 'Action']">
            @foreach ([
                ['PO-00231', 'Fournisseur Ivoire', 'Boulangerie Centre', '30/09/2026', 1_850_000, 'warning', 'En attente'],
                ['PO-00230', 'Minoterie Abidjan', 'Boulangerie Centre', '29/09/2026', 3_240_000, 'warning', 'Partiellement livrée'],
                ['PO-00229', 'Agro Supply', 'Boulangerie Cocody', '28/09/2026', 780_000, 'success', 'Validée'],
            ] as [$ref, $supplier, $site, $date, $total, $tone, $status])
                <tr>
                    <td class="font-bold">{{ $ref }}</td>
                    <td>{{ $supplier }}</td>
                    <td>{{ $site }}</td>
                    <td>{{ $date }}</td>
                    <td class="numeric text-right font-bold">{{ Format::money($total) }}</td>
                    <td><x-ui.status-badge :tone="$tone" :label="$status" :icon="false" /></td>
                    <td><x-ui.button variant="secondary" size="sm" onclick="window.toast('Commande {{ $ref }} ouverte')">Ouvrir</x-ui.button></td>
                </tr>
            @endforeach
        </x-ui.data-table>
    </x-ui.card>

    <x-ui.modal name="purchase-create" title="Nouvelle commande fournisseur" subtitle="Formulaire prototype">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            <x-ui.form-field name="reference" label="Fournisseur / référence" placeholder="Saisir…" />
            <x-ui.select name="site" :options="['Boulangerie Centre' => 'Boulangerie Centre', 'Boulangerie Cocody' => 'Boulangerie Cocody']" label="Site" />
            <x-ui.form-field name="amount" label="Montant estimé" type="number" inputmode="numeric" />
            <x-ui.form-field name="comment" label="Commentaire" placeholder="Optionnel" />
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" @click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" @click="open = false; window.toast('Commande enregistrée')">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</x-layouts.app>
