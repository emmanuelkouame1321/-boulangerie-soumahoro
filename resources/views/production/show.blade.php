@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Production {{ $order->reference }}">
    <x-layout.page-header
        eyebrow="Production • {{ $order->produced_on?->format('d/m/Y') }}"
        title="{{ $order->recipe?->product?->name ?? 'Production' }}"
        description="Ordre {{ $order->reference }} • {{ $order->bakery?->name ?? '—' }}"
    >
        <x-slot:actions>
            <x-ui.button variant="ghost" icon="arrow-left" :href="route('production.index')">Retour</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <x-ui.status-badge :tone="$order->status->tone()" :label="$order->status->label()" />
        <span class="text-[13px] text-muted">
            {{ Format::stock($order->batch_count) }} lot(s) • {{ $order->recipe?->name ?? '—' }}
        </span>
    </div>

    <x-business.production-summary
        class="mb-4"
        :theoretical="$order->theoretical_quantity"
        :actual="$order->actual_quantity"
        :gap="$order->gap"
        :cost="$order->actual_cost ?? $order->theoretical_cost"
        :unit="$order->recipe?->product?->primaryUnit?->symbol"
    />

    @if ($order->hasGap())
        <x-ui.alert tone="warning" title="Écart de rendement" class="mb-4">
            {{ Format::signed($order->gap) }} unités —
            motif : {{ config('boulangerie.production_gap_reasons.'.$order->gap_reason, $order->gap_reason ?? 'non précisé') }}.
        </x-ui.alert>
    @endif

    <div class="grid gap-4 xl:grid-cols-3">
        <x-ui.card title="Consommations" subtitle="Matières et ressources déduites" flush class="xl:col-span-2">
            @if ($order->items->isEmpty())
                <p class="p-4 text-[13px] text-muted">Aucune consommation enregistrée.</p>
            @else
                <x-ui.data-table :headers="['Ressource', 'Mode', 'Planifié', 'Réel', 'Coût unitaire', 'Total']">
                    @foreach ($order->items as $item)
                        <tr>
                            <td class="font-medium">{{ $item->product?->name ?? '—' }}</td>
                            <td class="text-muted">{{ $item->deduction_mode->label() }}</td>
                            <td class="numeric">{{ Format::stock($item->planned_quantity) }} {{ $item->product?->primaryUnit?->symbol ?? '' }}</td>
                            <td class="numeric">{{ $item->actual_quantity !== null ? Format::stock($item->actual_quantity) : '—' }}</td>
                            <td class="numeric">{{ Format::money($item->unit_cost) }}</td>
                            <td class="numeric font-bold">{{ Format::money($item->line_cost) }}</td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            @endif
        </x-ui.card>

        <x-ui.card title="Lots produits" subtitle="Produits finis et dates limites">
            @if ($order->batches->isEmpty())
                <p class="text-[13px] text-muted">Aucun lot : la production n’a pas encore été validée.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($order->batches as $batch)
                        <li class="rounded-control border border-line p-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-ink">{{ $batch->reference }}</span>
                                <x-ui.status-badge :tone="$batch->status->tone()" :label="$batch->status->label()" :icon="false" />
                            </div>
                            <p class="mt-1 text-[13px] text-muted">
                                {{ Format::stock($batch->available_quantity) }} disponibles
                                @if ($batch->expires_on)
                                    • à consommer avant le {{ $batch->expires_on->format('d/m/Y') }}
                                @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>
</x-layouts.app>
