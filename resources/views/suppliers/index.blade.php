<x-layouts.app title="Fournisseurs">
    <x-layout.page-header eyebrow="Approvisionnement" title="Fournisseurs">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" wire:click="$dispatch('open-modal', 'supplier-create')">Nouveau fournisseur</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card flush>
        <div class="flex flex-wrap gap-2 p-4">
            <x-ui.search-input placeholder="Rechercher un fournisseur…" />
            <x-ui.button variant="secondary">Tous les sites</x-ui.button>
            <x-ui.button variant="secondary">Statut</x-ui.button>
            <x-ui.button variant="secondary" icon="download" onclick="window.toast('Export en préparation')">Exporter</x-ui.button>
        </div>

        <x-ui.data-table :headers="['Fournisseur', 'Contact', 'Spécialité', ['label' => 'Commandes', 'align' => 'right'], 'Action']">
            @foreach ([
                ['Fournisseur Ivoire', '07 00 00 10 01', 'Farine, levure', '12 commandes'],
                ['Minoterie Abidjan', '05 00 00 20 02', 'Farine', '8 commandes'],
                ['Agro Supply', '01 00 00 30 03', 'Emballages, sel', '6 commandes'],
            ] as [$name, $contact, $specialty, $orders])
                <tr>
                    <td class="font-bold">{{ $name }}</td>
                    <td>{{ $contact }}</td>
                    <td>{{ $specialty }}</td>
                    <td class="numeric text-right">{{ $orders }}</td>
                    <td><x-ui.button variant="secondary" size="sm" onclick="window.toast('Fiche {{ $name }} ouverte')">Ouvrir</x-ui.button></td>
                </tr>
            @endforeach
        </x-ui.data-table>
    </x-ui.card>

    <x-ui.modal name="supplier-create" title="Nouveau fournisseur" subtitle="Formulaire prototype">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            <x-ui.form-field name="name" label="Nom / référence" placeholder="Saisir…" />
            <x-ui.form-field name="phone" label="Contact" inputmode="tel" />
            <x-ui.form-field name="specialty" label="Spécialité" placeholder="Farine, levure…" />
            <x-ui.select name="state" :options="['Actif' => 'Actif', 'En attente' => 'En attente']" label="Statut" />
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" @click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" @click="open = false; window.toast('Fournisseur enregistré')">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</x-layouts.app>
