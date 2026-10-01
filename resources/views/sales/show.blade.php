@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Vente {{ $sale->reference }}">
    <x-layout.page-header
        eyebrow="Commercial • {{ $sale->sale_date?->format('d/m/Y') }}"
        title="Vente {{ $sale->reference }}"
        description="{{ $sale->customer?->name ?? 'Comptoir' }} • {{ $sale->bakery?->name ?? '—' }}"
    >
        <x-slot:actions>
            <x-ui.button variant="ghost" icon="arrow-left" :href="route('sales.index')">Retour</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <x-ui.status-badge :tone="$sale->status->tone()" :label="$sale->status->label()" />
        <span class="text-[13px] text-muted">{{ $sale->type->label() }} • {{ $sale->items->count() }} article(s)</span>
    </div>

    <div class="mb-4 grid grid-cols-2 gap-3.5 xl:grid-cols-4">
        <x-ui.stat-card label="Sous-total" :value="Format::money($sale->subtotal)" icon="banknote" />
        <x-ui.stat-card label="Remise" :value="Format::money($sale->discount)" icon="badge-percent" />
        <x-ui.stat-card label="Total" :value="Format::money($sale->total)" icon="receipt" />
        <x-ui.stat-card label="Encaissé" :value="Format::money($sale->paid_total)" icon="wallet" />
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <x-ui.card title="Articles" subtitle="Produits finis vendus" flush class="xl:col-span-2">
            <x-ui.data-table :headers="['Produit', ['label' => 'Quantité', 'align' => 'right'], ['label' => 'Prix unitaire', 'align' => 'right'], ['label' => 'Remise', 'align' => 'right'], ['label' => 'Total', 'align' => 'right']]">
                @foreach ($sale->items as $item)
                    <tr>
                        <td class="font-medium">{{ $item->product?->name ?? '—' }}</td>
                        <td class="numeric text-right">{{ Format::stock($item->quantity) }}</td>
                        <td class="numeric text-right">{{ Format::money($item->unit_price) }}</td>
                        <td class="numeric text-right">{{ Format::money($item->discount) }}</td>
                        <td class="numeric text-right font-bold">{{ Format::money($item->line_total) }}</td>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </x-ui.card>

        <x-ui.card title="Paiements">
            @if ($sale->payments->isEmpty())
                <p class="text-[13px] text-muted">Aucun paiement enregistré.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($sale->payments as $payment)
                        <li class="rounded-control border border-line p-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-ink">{{ Format::money($payment->amount) }}</span>
                                <span class="text-[12px] text-muted">{{ $payment->method }}</span>
                            </div>
                            <p class="mt-1 text-[11px] text-soft">{{ $payment->paid_at?->format('d/m/Y H:i') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>
</x-layouts.app>
