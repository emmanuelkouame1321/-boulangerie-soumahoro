<x-layouts.app title="Notifications">
    <x-layout.page-header eyebrow="Centre d’alertes" title="Notifications">
        <x-slot:actions>
            <x-ui.button variant="secondary" onclick="window.toast('Notifications marquées comme lues')">Tout marquer comme lu</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-ui.card>
        <div class="divide-y divide-line">
            @foreach ([
                ['Stock critique', 'Farine 50 kg — 8 sacs restants', 'Il y a 5 min', 'danger', 'Critique'],
                ['Péremption proche', 'Croissants — lot #20261001-001', 'Il y a 18 min', 'warning', 'Attention'],
                ['Commande en retard', 'PO-00231 — Fournisseur Ivoire', 'Il y a 32 min', 'warning', 'Attention'],
                ['Écart de rendement', 'PRD-000184 — -8 pains', 'Il y a 1 h', 'info', 'Info'],
                ['Réception validée', 'PO-00229 — stock mis à jour', 'Il y a 2 h', 'success', 'Succès'],
            ] as [$title, $meta, $time, $tone, $label])
                <div class="flex gap-3 py-4">
                    <x-ui.status-badge :tone="$tone" :label="$label" :icon="false" />
                    <div class="min-w-0">
                        <p class="text-[13px] font-bold text-ink">{{ $title }}</p>
                        <p class="text-[13px] text-muted">{{ $meta }}</p>
                        <p class="mt-0.5 text-[11px] text-soft">{{ $time }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui.card>
</x-layouts.app>
