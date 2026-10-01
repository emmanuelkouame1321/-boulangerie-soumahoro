@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Charges">
    <x-layout.page-header eyebrow="Dépenses d’exploitation" title="Charges">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" wire:click="$dispatch('open-modal', 'expense-create')">Ajouter une charge</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-3.5 md:grid-cols-3">
            <x-ui.card><p class="label-caps text-muted">Ce mois</p><p class="numeric mt-1 text-2xl font-extrabold">{{ Format::money(4_280_000) }}</p></x-ui.card>
            <x-ui.card><p class="label-caps text-muted">Électricité</p><p class="numeric mt-1 text-2xl font-extrabold">{{ Format::money(1_240_000) }}</p></x-ui.card>
            <x-ui.card><p class="label-caps text-muted">Maintenance</p><p class="numeric mt-1 text-2xl font-extrabold">{{ Format::money(680_000) }}</p></x-ui.card>
        </div>

        <x-ui.card flush>
            <x-ui.data-table :headers="['Date', 'Catégorie', 'Site', 'Bénéficiaire', ['label' => 'Montant', 'align' => 'right'], 'Paiement']">
                @foreach ([
                    ['30/09/2026', 'Électricité', 'Centre', 'Compagnie Ivoirienne', 420_000, 'Virement'],
                    ['29/09/2026', 'Maintenance', 'Cocody', 'Tech Services', 180_000, 'Espèces'],
                ] as [$date, $category, $site, $payee, $amount, $method])
                    <tr>
                        <td>{{ $date }}</td>
                        <td>{{ $category }}</td>
                        <td>{{ $site }}</td>
                        <td>{{ $payee }}</td>
                        <td class="numeric text-right font-bold">{{ Format::money($amount) }}</td>
                        <td>{{ $method }}</td>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </x-ui.card>
    </div>

    <x-ui.modal name="expense-create" title="Nouvelle charge" subtitle="Formulaire prototype">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            <x-ui.select name="category" :options="['Électricité' => 'Électricité', 'Maintenance' => 'Maintenance', 'Loyer' => 'Loyer', 'Transport' => 'Transport']" label="Catégorie" />
            <x-ui.form-field name="amount" label="Montant" type="number" inputmode="numeric" />
            <x-ui.form-field name="payee" label="Bénéficiaire" />
            <x-ui.select name="method" :options="config('boulangerie.payment_methods')" label="Mode de paiement" />
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" @click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" @click="open = false; window.toast('Charge enregistrée')">Enregistrer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</x-layouts.app>
