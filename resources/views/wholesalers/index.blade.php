@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Grossistes">
    <x-layout.page-header eyebrow="Commercial" title="Grossistes">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" wire:click="$dispatch('open-modal', 'wholesaler-create')">Nouveau grossiste</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-3.5 md:grid-cols-3">
            <x-ui.card><p class="label-caps text-muted">Grossistes actifs</p><p class="numeric mt-1 text-3xl font-extrabold">24</p><p class="mt-1 text-[11px] text-soft">+3 ce mois</p></x-ui.card>
            <x-ui.card><p class="label-caps text-muted">CA annuel</p><p class="numeric mt-1 text-3xl font-extrabold">184,5 M</p><p class="mt-1 text-[11px] text-soft">FCFA</p></x-ui.card>
            <x-ui.card><p class="label-caps text-muted">Ristournes projetées</p><p class="numeric mt-1 text-3xl font-extrabold">3,2 M</p><p class="mt-1 text-[11px] text-soft">FCFA</p></x-ui.card>
        </div>

        <x-ui.card flush>
            <x-ui.data-table :headers="['Grossiste', 'Contact', ['label' => 'Achats annuels', 'align' => 'right'], ['label' => 'CA', 'align' => 'right'], 'Prix', 'Statut']">
                @foreach ([
                    ['Boulangerie ABC', '07 00 00 00 01', '82 400 pains', 41_200_000, 'Prix grossiste', 'Actif'],
                    ['Hôtel XYZ', '05 00 00 00 02', '54 200 unités', 32_800_000, 'Contrat B', 'Actif'],
                    ['Restaurant Chez Nous', '01 00 00 00 03', '31 800 unités', 18_700_000, 'Prix grossiste', 'Actif'],
                ] as [$name, $contact, $volume, $revenue, $price, $status])
                    <tr>
                        <td class="font-bold">{{ $name }}</td>
                        <td>{{ $contact }}</td>
                        <td class="numeric text-right">{{ $volume }}</td>
                        <td class="numeric text-right font-bold">{{ Format::money($revenue) }}</td>
                        <td>{{ $price }}</td>
                        <td><x-ui.status-badge tone="success" :label="$status" :icon="false" /></td>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </x-ui.card>
    </div>

    <x-ui.modal name="wholesaler-create" title="Nouveau grossiste" subtitle="Formulaire prototype">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            <x-ui.form-field name="name" label="Nom / référence" placeholder="Saisir…" />
            <x-ui.form-field name="phone" label="Contact" inputmode="tel" />
            <x-ui.select name="site" :options="['Boulangerie Centre' => 'Boulangerie Centre', 'Boulangerie Cocody' => 'Boulangerie Cocody']" label="Site" />
            <x-ui.select name="price" :options="['Prix grossiste' => 'Prix grossiste', 'Contrat B' => 'Contrat B']" label="Grille de prix" />
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" @click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" @click="open = false; window.toast('Grossiste enregistré')">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</x-layouts.app>
