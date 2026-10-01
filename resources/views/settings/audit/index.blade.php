<x-layouts.app title="Journal d’audit">
    <x-layout.page-header eyebrow="Traçabilité des opérations" title="Journal d’audit">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="download" onclick="window.toast('Export en préparation')">Exporter</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card flush>
        <div class="flex flex-wrap gap-2 p-4">
            <x-ui.search-input placeholder="Utilisateur, objet, référence…" />
            <x-ui.select name="action" :options="['Vente validée' => 'Vente validée', 'Production' => 'Production', 'Stock' => 'Stock', 'Paramétrage' => 'Paramétrage']" placeholder="Toutes les actions" class="min-w-48" />
            <x-ui.button variant="secondary" icon="calendar" onclick="window.toast('Sélecteur de période')">Période</x-ui.button>
        </div>

        <x-ui.data-table :headers="['Utilisateur', 'Action', 'Objet', 'Site', 'Date', '']">
            @foreach ([
                ['Kouassi Jean', 'Production validée', 'PRD-000183', 'Centre', '01/10/2026 15:42'],
                ['Awa Kouassi', 'Vente validée', 'SALE-00045', 'Centre', '01/10/2026 15:38'],
                ['Admin', 'Prix modifié', 'SKU-BAGUETTE', 'Tous', '01/10/2026 14:12'],
            ] as [$user, $action, $object, $site, $date])
                <tr>
                    <td class="font-bold">{{ $user }}</td>
                    <td>{{ $action }}</td>
                    <td>{{ $object }}</td>
                    <td><x-ui.status-badge tone="info" :label="$site" :icon="false" /></td>
                    <td>{{ $date }}</td>
                    <td><x-ui.button variant="secondary" size="sm" wire:click="$dispatch('open-modal', 'audit-detail')">Détails</x-ui.button></td>
                </tr>
            @endforeach
        </x-ui.data-table>
    </x-ui.card>

    <x-ui.modal name="audit-detail" title="Détail de l’opération" subtitle="SALE-00045 • audit complet" max-width="max-w-lg">
        <div class="ml-2 space-y-4 border-l-2 border-line pl-5">
            @foreach ([
                ['Qui ?', 'Awa Kouassi • Caissière'],
                ['Quoi ?', 'Vente validée'],
                ['Quand ?', '01/10/2026 à 15:38'],
                ['Où ?', 'Boulangerie Centre'],
                ['Résultat', '3 300 FCFA • espèces • 3 lignes'],
            ] as [$question, $answer])
                <div class="relative">
                    <span class="absolute top-1 -left-[27px] size-2.5 rounded-full bg-brand-500 ring-2 ring-white ring-offset-1 ring-offset-brand-200"></span>
                    <p class="text-[13px] font-bold text-ink">{{ $question }}</p>
                    <p class="text-[13px] text-muted">{{ $answer }}</p>
                </div>
            @endforeach
        </div>

        <x-slot:footer>
            <x-ui.button variant="secondary" @click="open = false">Fermer</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</x-layouts.app>
