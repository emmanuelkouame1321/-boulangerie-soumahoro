@php
    use App\Support\Format;
    use App\Support\Decimal;
@endphp

<x-layouts.app :title="$order->reference">
    <x-layout.page-header
        eyebrow="Commande fournisseur"
        :title="$order->reference"
        :description="$order->supplier?->name"
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="arrow-left" :href="route('purchasing.orders.index')">Retour</x-ui.button>

            @if ($canCancel)
                <form method="POST" action="{{ route('purchasing.orders.cancel', $order) }}" onsubmit="return confirm('Annuler cette commande ?');">
                    @csrf
                    <x-ui.button type="submit" variant="danger" icon="x">Annuler la commande</x-ui.button>
                </form>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    @if (session('error'))
        <x-ui.alert tone="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-3.5 xl:grid-cols-4">
        <x-ui.stat-card label="Statut" :value="$order->status->label()" icon="clipboard-list" />
        <x-ui.stat-card label="Total commandé" :value="Format::money($order->total)" icon="receipt-text" />
        <x-ui.stat-card label="Lignes" :value="Format::number($order->items->count())" icon="list" />
        <x-ui.stat-card
            label="Livraison attendue"
            :value="$order->expected_at?->format('d/m/Y') ?? '—'"
            icon="calendar"
        />
    </div>

    <div class="mt-4 space-y-4">
        <x-ui.card title="Lignes commandées" subtitle="Contrôle des écarts entre quantité commandée et quantité reçue." flush>
            <x-ui.data-table :headers="['Produit', ['label' => 'Commandé', 'align' => 'right'], ['label' => 'Reçu', 'align' => 'right'], ['label' => 'Reste', 'align' => 'right'], ['label' => 'Écart', 'align' => 'right'], 'Prix unitaire', ['label' => 'Total ligne', 'align' => 'right']]">
                @foreach ($order->items as $item)
                    <tr>
                        <td>
                            <span class="block font-bold text-ink">{{ $item->product?->name ?? '—' }}</span>
                            <span class="block text-[11px] text-soft">{{ $item->product?->sku }}</span>
                        </td>
                        <td class="numeric text-right">
                            {{ Format::stock($item->quantity) }}
                            <span class="text-soft">{{ $item->unit?->symbol ?? $item->product?->primaryUnit?->symbol }}</span>
                        </td>
                        <td class="numeric text-right font-bold">{{ Format::stock($item->received_quantity) }}</td>
                        <td class="numeric text-right">{{ Format::stock($item->remainingQuantity()) }}</td>
                        <td class="numeric text-right">
                            @if (Decimal::isNegative($item->gap()) && Decimal::compare($item->received_quantity, '0') > 0)
                                <span class="font-bold text-warning">{{ Format::signed($item->gap()) }}</span>
                            @else
                                {{ Format::signed($item->gap()) }}
                            @endif
                        </td>
                        <td class="numeric">{{ Format::money($item->unit_price) }}</td>
                        <td class="numeric text-right font-bold">{{ Format::money($item->line_total) }}</td>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </x-ui.card>

        @if ($canReceive)
            <x-ui.card title="Enregistrer une réception" subtitle="Saisissez les quantités réellement livrées, puis validez. Une réception validée est définitive.">
                <form method="POST" action="{{ route('purchasing.receipts.store', $order) }}" class="space-y-4">
                    @csrf

                    <x-ui.data-table :headers="['Produit', ['label' => 'Restant', 'align' => 'right'], ['label' => 'Quantité reçue', 'align' => 'right']]">
                        @foreach ($order->items as $index => $item)
                            <tr>
                                <td>
                                    <span class="block font-bold text-ink">{{ $item->product?->name ?? '—' }}</span>
                                    <span class="block text-[11px] text-soft">{{ $item->product?->sku }}</span>
                                </td>
                                <td class="numeric text-right">{{ Format::stock($item->remainingQuantity()) }}</td>
                                <td class="text-right">
                                    <input type="hidden" name="items[{{ $index }}][purchase_order_item_id]" value="{{ $item->getKey() }}">
                                    <input
                                        type="number"
                                        step="any"
                                        min="0"
                                        inputmode="decimal"
                                        name="items[{{ $index }}][quantity]"
                                        value="{{ old('items.'.$index.'.quantity', $item->remainingQuantity()) }}"
                                        class="numeric ml-auto block w-32 rounded-control border border-line-strong bg-surface px-3 py-2 text-right text-sm text-ink transition focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                                    >
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.data-table>

                    <x-ui.form-field name="note" label="Observation de réception" help="Facultatif." />

                    <div class="flex justify-end">
                        <x-ui.button type="submit" variant="primary" icon="package-check">Valider la réception</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @endif

        <x-ui.card title="Historique des réceptions" flush>
            @if ($order->receipts->isEmpty())
                <div class="p-4">
                    <x-ui.empty-state icon="package" title="Aucune réception" description="Les réceptions validées apparaîtront ici." />
                </div>
            @else
                <x-ui.data-table :headers="['Réception', 'Reçue le', 'Validée le', 'Statut']">
                    @foreach ($order->receipts as $receipt)
                        <tr>
                            <td class="font-bold">{{ $receipt->reference }}</td>
                            <td>{{ $receipt->received_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $receipt->validated_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>
                                <x-ui.status-badge :tone="$receipt->status->tone()" :label="$receipt->status->label()" :icon="false" />
                            </td>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            @endif
        </x-ui.card>
    </div>
</x-layouts.app>
