@php
    use App\Support\Format;

    // Contenu du panier, partagé entre la colonne (grand écran) et le
    // tiroir bas (mobile). `$scope` évite toute collision de `wire:key`.
    $drawer = $drawer ?? false;
    $scope = $scope ?? 'desktop';
@endphp

<div class="flex items-center justify-between border-b border-line px-4 py-3">
    <div>
        <p class="text-sm font-bold text-ink">Panier</p>
        <p class="text-[11px] text-soft">{{ now()->format('H:i') }}</p>
    </div>

    <div class="flex items-center gap-2">
        <x-ui.status-badge tone="success" :label="$itemCount.' article'.($itemCount > 1 ? 's' : '')" :icon="false" />

        @if ($drawer)
            <button
                type="button"
                x-on:click="cartOpen = false"
                class="rounded-control p-1.5 text-muted hover:bg-subtle"
                aria-label="Fermer le panier"
            >
                <x-ui.icon name="x" :size="18" />
            </button>
        @endif
    </div>
</div>

<div class="flex-1 overflow-y-auto">
    @forelse ($cart as $line)
        <div wire:key="{{ $scope }}-cart-{{ $line['product_id'] }}" class="border-b border-line">
            <div class="flex items-center justify-between px-3 pt-3.5 pb-1.5">
                <span class="text-[15px] font-medium uppercase">{{ $line['name'] }}</span>
                <span class="numeric text-[15px] font-bold">{{ Format::money($line['price'] * $line['qty']) }}</span>
            </div>

            <div class="flex items-center gap-1.5 px-3 pb-2.5">
                <button type="button" wire:click="changeQuantity({{ $line['product_id'] }}, -1)" class="grid size-8 place-items-center rounded-control border border-line-strong font-extrabold hover:bg-subtle">−</button>
                <span class="numeric w-8 text-center text-sm font-bold">{{ $line['qty'] }}</span>
                <button type="button" wire:click="changeQuantity({{ $line['product_id'] }}, 1)" class="grid size-8 place-items-center rounded-control border border-line-strong font-extrabold hover:bg-subtle">+</button>
                <button type="button" wire:click="removeItem({{ $line['product_id'] }})" class="ml-auto rounded-control border border-line-strong p-2 hover:bg-subtle" aria-label="Retirer">
                    <x-ui.icon name="trash-2" :size="15" />
                </button>
            </div>
        </div>
    @empty
        <div class="p-4">
            <x-ui.empty-state title="Panier vide" description="Ajoutez des produits pour commencer la vente." icon="shopping-bag" />
        </div>
    @endforelse
</div>

<div class="border-t border-line p-4">
    <div class="flex justify-between text-[13px]">
        <span class="text-muted">Sous-total</span>
        <span class="numeric font-bold">{{ Format::money($subtotal) }}</span>
    </div>
    <div class="mt-1.5 flex justify-between text-[13px]">
        <span class="text-muted">Remise</span>
        <span class="numeric font-bold">{{ Format::money(0) }}</span>
    </div>
    <div class="mt-1.5 flex justify-between text-[13px]">
        <span class="text-muted">Taxes</span>
        <span class="numeric font-bold">{{ Format::money(0) }}</span>
    </div>

    <div class="mt-2.5 mb-3.5 flex justify-between">
        <span class="text-[13px] font-bold uppercase">Total</span>
        <span class="numeric text-[22px] font-bold">{{ Format::money($subtotal) }}</span>
    </div>

    <x-ui.button variant="secondary" class="mb-2 w-full" onclick="window.toast('Mémo enregistré')">Mémo</x-ui.button>

    <button
        type="button"
        wire:click="openPayment"
        x-on:click="cartOpen = false"
        class="min-h-14 w-full rounded-control bg-brand-700 text-[15px] font-bold tracking-wide text-white uppercase transition hover:bg-brand-800"
    >
        Payer • {{ Format::money($subtotal) }}
    </button>
</div>
