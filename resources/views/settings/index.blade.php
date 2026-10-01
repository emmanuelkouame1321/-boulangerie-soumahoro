@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Paramètres">
    <x-layout.page-header eyebrow="Administration" title="Paramètres">
        <x-slot:actions>
            <x-ui.button variant="primary" onclick="window.toast('Paramètres enregistrés')">Enregistrer</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <x-ui.card title="Général">
            <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                <x-ui.form-field name="site_name" label="Nom du site" value="Boulangerie Centre" />
                <x-ui.form-field name="site_code" label="Code" value="ABJ-CENTRE" />
                <x-ui.form-field name="phone" label="Téléphone" value="+225 07 00 00 00 00" />
                <x-ui.select name="currency" :options="[config('boulangerie.currency.label') => config('boulangerie.currency.label')]" label="Devise" />
            </div>
        </x-ui.card>

        <x-ui.card title="Vente">
            <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                <x-ui.select name="cash" :options="['Activé' => 'Activé', 'Désactivé' => 'Désactivé']" label="Paiement espèces" />
                <x-ui.select name="mobile_money" :options="['Activé' => 'Activé', 'Désactivé' => 'Désactivé']" label="Mobile Money" />
                <x-ui.select name="scanner" :options="['Activé' => 'Activé', 'Désactivé' => 'Désactivé']" label="Scanner code-barres" />
                <x-ui.select name="printing" :options="['Après validation' => 'Après validation', 'Manuel' => 'Manuel', 'Désactivé' => 'Désactivé']" label="Impression ticket" />
            </div>
        </x-ui.card>

        <x-ui.card title="Production">
            <div class="grid gap-3">
                @foreach (['FEFO pour produits périssables', 'Bloquer production si matière insuffisante', 'Créer automatiquement les lots', 'Alerter sur écart de rendement'] as $option)
                    <label class="flex items-center gap-2.5 text-[13px]">
                        <input type="checkbox" checked class="size-4 rounded-control border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-100">
                        {{ $option }}
                    </label>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card title="Notifications">
            <div class="grid gap-3">
                @foreach (['Stock critique', 'Produit proche péremption', 'Commande en retard', 'Production insuffisante'] as $option)
                    <label class="flex items-center justify-between gap-3 text-[13px]">
                        {{ $option }}
                        <input type="checkbox" checked class="size-4 rounded-control border-line-strong text-brand-600 focus:ring-2 focus:ring-brand-100">
                    </label>
                @endforeach
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
