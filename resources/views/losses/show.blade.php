@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Détail de la perte">
    <x-layout.page-header
        eyebrow="Contrôle des écarts"
        :title="$loss->reference"
        description="Registre de perte — opération historique, jamais supprimée."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" :href="route('losses.index')">Retour</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-3.5 xl:grid-cols-4">
            <x-ui.stat-card label="Valeur de la perte" :value="Format::money($loss->total_value)" icon="banknote" />
            <x-ui.stat-card label="Quantité totale" :value="Format::stock($loss->total_quantity)" icon="package-x" />
            <x-ui.stat-card label="Motif" :value="$loss->reason->label()" icon="triangle-alert" />
            <x-ui.stat-card label="Origine" :value="$loss->origin->label()" icon="factory" />
        </div>

        <x-ui.card>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-[11px] font-bold tracking-wide text-soft uppercase">Site</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $loss->bakery?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold tracking-wide text-soft uppercase">Date</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $loss->occurred_on?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-bold tracking-wide text-soft uppercase">Déclarée par</dt>
                    <dd class="mt-1 text-sm text-ink">{{ $loss->createdBy?->name ?? 'Système (péremption)' }}</dd>
                </div>
                @if ($loss->note !== null)
                    <div class="sm:col-span-3">
                        <dt class="text-[11px] font-bold tracking-wide text-soft uppercase">Observation</dt>
                        <dd class="mt-1 text-sm text-ink">{{ $loss->note }}</dd>
                    </div>
                @endif
            </dl>
        </x-ui.card>

        <x-ui.card flush>
            <div class="px-4 pt-4">
                <h2 class="text-sm font-bold text-ink">Lignes de perte</h2>
            </div>

            <div class="mt-3">
                <x-ui.data-table :headers="['Produit', 'Lot', ['label' => 'Quantité', 'align' => 'right'], ['label' => 'Prix unitaire', 'align' => 'right'], ['label' => 'Valeur', 'align' => 'right'], 'Commentaire']">
                    @foreach ($loss->items as $item)
                        <tr>
                            <td class="font-medium">{{ $item->product?->name ?? '—' }}</td>
                            <td>{{ $item->batch?->reference ?? 'Attribution FEFO' }}</td>
                            <td class="numeric text-right">{{ Format::stock($item->quantity) }}</td>
                            <td class="numeric text-right">{{ Format::money($item->unit_value) }}</td>
                            <td class="numeric text-right font-bold">{{ Format::money($item->line_value) }}</td>
                            <td class="text-muted">{{ $item->comment ?? '—' }}</td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
