@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Production">
    <x-layout.page-header eyebrow="Opérations • Aujourd’hui" title="Production">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" wire:click="$dispatch('open-modal', 'production-create')">Nouvelle production</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
            <x-ui.stat-card label="Productions en cours" :value="Format::number(4)" trend="2 lots à valider" trend-tone="success" icon="factory" />
            <x-ui.stat-card label="Unités attendues" :value="Format::number(3_330)" trend="Rendement théorique" icon="calculator" />
            <x-ui.stat-card label="Unités réelles" :value="Format::number(3_240)" trend="-90 unités" trend-tone="warning" icon="package-check" />
            <x-ui.stat-card label="Écart" :value="Format::percent(-2.7, 1)" trend="À justifier" trend-tone="warning" icon="scale" />
        </div>

        <x-ui.card flush>
            <div class="flex flex-wrap gap-2 p-4">
                <x-ui.search-input placeholder="Rechercher une production…" />
                <x-ui.select name="status" :options="['En cours' => 'En cours', 'Terminée' => 'Terminée', 'À valider' => 'À valider']" placeholder="Tous les statuts" class="min-w-44" />
                <x-ui.button variant="secondary" onclick="window.toast('Filtre : aujourd’hui')">Aujourd’hui</x-ui.button>
            </div>

            <x-ui.data-table :headers="['Ordre', 'Produit / recette', 'Lots', ['label' => 'Théorique', 'align' => 'right'], ['label' => 'Réel', 'align' => 'right'], ['label' => 'Rendement', 'align' => 'right'], 'Statut', '']">
                @foreach ([
                    ['PRD-000184', 'Pain traditionnel', '1 sac', '300', '292', '97,3 %', 'warning', 'À valider'],
                    ['PRD-000183', 'Croissant', '4 lots', '800', '800', '100 %', 'success', 'Terminée'],
                    ['PRD-000182', 'Cake nature', '2 lots', '60', '58', '96,7 %', 'warning', 'À valider'],
                    ['PRD-000181', 'Gâteau chocolat', '1 lot', '12', '12', '100 %', 'success', 'Terminée'],
                ] as [$ref, $product, $lots, $theoretical, $real, $yield, $tone, $status])
                    <tr>
                        <td class="font-bold">{{ $ref }}</td>
                        <td>{{ $product }}</td>
                        <td>{{ $lots }}</td>
                        <td class="numeric text-right">{{ $theoretical }}</td>
                        <td class="numeric text-right">{{ $real }}</td>
                        <td class="numeric text-right">{{ $yield }}</td>
                        <td><x-ui.status-badge :tone="$tone" :label="$status" :icon="false" /></td>
                        <td><x-ui.button variant="secondary" size="sm" onclick="window.toast('Fiche {{ $ref }} ouverte')">Ouvrir</x-ui.button></td>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </x-ui.card>
    </div>

    {{-- Modale de création (assistant 3 étapes) --}}
    <x-ui.modal name="production-create" title="Nouvelle production" subtitle="Calcul automatique des besoins et du rendement">
        <div class="mb-5 flex gap-1">
            <div class="flex-1 border-b-2 border-brand-500 pb-2 text-xs font-bold text-brand-800">1. Configuration</div>
            <div class="flex-1 border-b-2 border-line pb-2 text-xs text-muted">2. Contrôle</div>
            <div class="flex-1 border-b-2 border-line pb-2 text-xs text-muted">3. Validation</div>
        </div>

        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
            <x-ui.select name="site" :options="['Boulangerie Centre' => 'Boulangerie Centre']" label="Site" />
            <x-ui.select name="recipe" :options="['Pain traditionnel' => 'Pain traditionnel', 'Croissant' => 'Croissant', 'Cake nature' => 'Cake nature']" label="Recette" />
            <x-ui.form-field name="lots" label="Lots / sacs" type="number" value="1" />
            <x-ui.form-field name="date" label="Date" type="date" value="2026-10-01" />
        </div>

        <x-ui.card class="mt-4" title="Besoins calculés" subtitle="Pour 1 sac • rendement théorique 300 pains">
            <x-slot:actions>
                <x-ui.status-badge tone="success" label="Stock disponible" :icon="false" />
            </x-slot:actions>

            <x-ui.data-table :headers="['Ressource', ['label' => 'Quantité', 'align' => 'right'], 'Disponibilité']">
                <tr><td>Farine</td><td class="numeric text-right">1 sac</td><td><x-ui.status-badge tone="success" label="24 disponibles" :icon="false" /></td></tr>
                <tr><td>Améliorant</td><td class="numeric text-right">1 paquet</td><td><x-ui.status-badge tone="success" label="16 disponibles" :icon="false" /></td></tr>
                <tr><td>Levure</td><td class="numeric text-right">1 paquet</td><td><x-ui.status-badge tone="warning" label="8 disponibles" :icon="false" /></td></tr>
            </x-ui.data-table>
        </x-ui.card>

        <x-slot:footer>
            <x-ui.button variant="secondary" @click="open = false">Annuler</x-ui.button>
            <x-ui.button variant="primary" @click="open = false; window.toast('Ordre de production créé')">Créer la production</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</x-layouts.app>
