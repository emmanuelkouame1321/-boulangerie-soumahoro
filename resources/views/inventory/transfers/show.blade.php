@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Détail du transfert">
    <x-layout.page-header
        eyebrow="Logistique"
        :title="$transfer->reference"
        description="Transfert inter-sites — document historique, jamais supprimé."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" :href="route('inventory.transfers.index')">Retour</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
            <x-ui.stat-card label="Statut" :value="$transfer->status->label()" icon="truck" />
            <x-ui.stat-card label="Quantité" :value="Format::stock($transfer->quantity)" icon="package" />
            <x-ui.stat-card label="Site expéditeur" :value="$transfer->sourceBakery?->name ?? '—'" icon="building-2" />
            <x-ui.stat-card label="Site destinataire" :value="$transfer->destinationBakery?->name ?? '—'" icon="store" />
        </div>

        @if ($transfer->isDraft() && ($canSend || $canCancel))
            <x-ui.card title="Actions du brouillon" subtitle="Aucun stock n'a encore bougé.">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($canSend)
                        <form method="POST" action="{{ route('inventory.transfers.send', $transfer) }}">
                            @csrf
                            <x-ui.button type="submit" variant="primary" icon="upload">Expédier</x-ui.button>
                        </form>
                    @endif

                    @if ($canCancel)
                        <form method="POST" action="{{ route('inventory.transfers.cancel', $transfer) }}">
                            @csrf
                            @method('POST')
                            <x-ui.button type="submit" variant="danger" icon="x">Annuler le transfert</x-ui.button>
                        </form>
                    @endif
                </div>
            </x-ui.card>
        @endif

        @if ($transfer->isSent() && $canReceive)
            <x-ui.card title="Réception" subtitle="Confirmez l'arrivée de la marchandise pour faire entrer le stock.">
                <form method="POST" action="{{ route('inventory.transfers.receive', $transfer) }}">
                    @csrf
                    <x-ui.button type="submit" variant="primary" icon="download">Réceptionner</x-ui.button>
                </form>
            </x-ui.card>
        @endif

        <x-ui.card>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-[11px] font-bold tracking-wide text-soft uppercase">Produit</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $transfer->product?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold tracking-wide text-soft uppercase">Lot désigné</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $transfer->batch?->reference ?? 'Imputation FEFO' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold tracking-wide text-soft uppercase">Date du transfert</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $transfer->transferred_on?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold tracking-wide text-soft uppercase">Préparé par</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $transfer->createdBy?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold tracking-wide text-soft uppercase">Expédié par</dt>
                    <dd class="mt-1 text-sm text-ink">
                        {{ $transfer->sentBy?->name ?? '—' }}
                        @if ($transfer->sent_at !== null)
                            <span class="text-muted">· {{ $transfer->sent_at->format('d/m/Y H:i') }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold tracking-wide text-soft uppercase">Réceptionné par</dt>
                    <dd class="mt-1 text-sm text-ink">
                        {{ $transfer->receivedBy?->name ?? '—' }}
                        @if ($transfer->received_at !== null)
                            <span class="text-muted">· {{ $transfer->received_at->format('d/m/Y H:i') }}</span>
                        @endif
                    </dd>
                </div>
                @if ($transfer->reason !== null)
                    <div class="sm:col-span-3">
                        <dt class="text-[11px] font-bold tracking-wide text-soft uppercase">Motif</dt>
                        <dd class="mt-1 text-sm text-ink">{{ $transfer->reason }}</dd>
                    </div>
                @endif
                @if ($transfer->note !== null)
                    <div class="sm:col-span-3">
                        <dt class="text-[11px] font-bold tracking-wide text-soft uppercase">Observation</dt>
                        <dd class="mt-1 text-sm text-ink">{{ $transfer->note }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>
    </div>
</x-layouts.app>
