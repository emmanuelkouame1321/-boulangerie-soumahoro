<x-layouts.app title="Employés">
    <x-layout.page-header eyebrow="Ressources humaines" title="Employés">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" wire:click="$dispatch('open-modal', 'employee-create')">Nouvel employé</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card flush>
        <div class="flex flex-wrap gap-2 p-4">
            <x-ui.search-input placeholder="Rechercher un employé…" />
            <x-ui.button variant="secondary">Tous les sites</x-ui.button>
            <x-ui.button variant="secondary">Poste</x-ui.button>
            <x-ui.button variant="secondary" icon="download" onclick="window.toast('Export en préparation')">Exporter</x-ui.button>
        </div>

        <x-ui.data-table :headers="['Employé', 'Poste', 'Site', 'Contrat', 'Action']">
            @foreach ([
                ['Kouassi Jean', 'Responsable production', 'Boulangerie Centre', 'CDI'],
                ['Awa Kouassi', 'Caissière', 'Boulangerie Centre', 'CDI'],
                ['Mariam Yao', 'Responsable vente', 'Boulangerie Cocody', 'CDI'],
            ] as [$name, $role, $site, $contract])
                <tr>
                    <td class="font-bold">{{ $name }}</td>
                    <td>{{ $role }}</td>
                    <td>{{ $site }}</td>
                    <td><x-ui.status-badge tone="success" :label="$contract" :icon="false" /></td>
                    <td><x-ui.button variant="secondary" size="sm" onclick="window.toast('Fiche {{ $name }} ouverte')">Ouvrir</x-ui.button></td>
                </tr>
            @endforeach
        </x-ui.data-table>
    </x-ui.card>

    <x-ui.modal name="employee-create" title="Nouvel employé" subtitle="Formulaire prototype">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            <x-ui.form-field name="name" label="Nom / référence" placeholder="Saisir…" />
            <x-ui.form-field name="position" label="Poste" />
            <x-ui.select name="site" :options="['Boulangerie Centre' => 'Boulangerie Centre', 'Boulangerie Cocody' => 'Boulangerie Cocody']" label="Site" />
            <x-ui.select name="contract" :options="['CDI' => 'CDI', 'CDD' => 'CDD', 'Journalier' => 'Journalier']" label="Contrat" />
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" @click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" @click="open = false; window.toast('Employé enregistré')">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</x-layouts.app>
