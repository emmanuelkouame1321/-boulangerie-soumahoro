@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Clients particuliers">
    <x-layout.page-header eyebrow="Clientèle" title="Clients particuliers">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" wire:click="$dispatch('open-modal', 'customer-create')">Ajouter un client</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card flush>
        <div class="flex flex-wrap gap-2 p-4">
            <x-ui.search-input placeholder="Rechercher un client…" />
            <x-ui.button variant="secondary">Tous les sites</x-ui.button>
            <x-ui.button variant="secondary">Statut</x-ui.button>
            <x-ui.button variant="secondary" icon="download" onclick="window.toast('Export en préparation')">Exporter</x-ui.button>
        </div>

        <x-ui.data-table :headers="['Client', 'Téléphone', ['label' => 'Ventes', 'align' => 'right'], ['label' => 'CA cumulé', 'align' => 'right'], 'Action']">
            @foreach ([
                ['CLI-00031', 'Client comptoir', '07 00 00 00 00', 42, 125_000],
                ['CLI-00030', 'Nadia Kouamé', '05 00 00 00 00', 18, 86_000],
                ['CLI-00029', 'Moussa Traoré', '01 00 00 00 00', 12, 54_500],
            ] as [$ref, $name, $phone, $sales, $revenue])
                <tr>
                    <td>
                        <span class="block font-bold text-ink">{{ $name }}</span>
                        <span class="block text-[11px] text-soft">{{ $ref }}</span>
                    </td>
                    <td>{{ $phone }}</td>
                    <td class="numeric text-right">{{ $sales }}</td>
                    <td class="numeric text-right font-bold">{{ Format::money($revenue) }}</td>
                    <td><x-ui.button variant="secondary" size="sm" onclick="window.toast('Fiche {{ $name }} ouverte')">Ouvrir</x-ui.button></td>
                </tr>
            @endforeach
        </x-ui.data-table>
    </x-ui.card>

    <x-ui.modal name="customer-create" title="Ajouter un client" subtitle="Formulaire prototype">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            <x-ui.form-field name="name" label="Nom / référence" placeholder="Saisir…" />
            <x-ui.form-field name="phone" label="Téléphone" inputmode="tel" placeholder="07 00 00 00 00" />
            <x-ui.select name="site" :options="['Boulangerie Centre' => 'Boulangerie Centre', 'Boulangerie Cocody' => 'Boulangerie Cocody']" label="Site" />
            <x-ui.form-field name="comment" label="Commentaire" placeholder="Optionnel" />
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" @click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" @click="open = false; window.toast('Client enregistré')">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</x-layouts.app>
