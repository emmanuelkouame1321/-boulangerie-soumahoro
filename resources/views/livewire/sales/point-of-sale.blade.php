@php
    use App\Support\Format;
@endphp

<div class="grid h-screen grid-rows-[60px_1fr]">
    {{-- En-tête de caisse --}}
    <div class="mt-1.5 flex items-center gap-2 border-b border-line bg-surface px-3">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-control px-2 py-2 text-ink transition hover:bg-subtle">
            <x-ui.icon name="arrow-left" :size="18" />
            <span class="hidden text-[13px] font-medium uppercase sm:inline">Quitter</span>
        </a>

        <x-ui.status-badge tone="success" label="Caisse active" :icon="false" />

        <div class="ml-2 hidden items-center gap-2 md:flex">
            @foreach ($paymentMethods as $key => $label)
                <span class="rounded-control border border-context-600 px-3 py-1.5 text-[11px] font-medium tracking-wide text-context-600 uppercase">
                    {{ $label }}
                </span>
            @endforeach
        </div>

        <div class="ml-auto flex items-center gap-2">
            <x-ui.status-badge tone="info" :label="app(\App\Domain\Sites\BakeryContext::class)->current()?->name ?? 'Toutes les boulangeries'" :icon="false" />
            <span class="hidden text-[11px] text-soft sm:inline">Caissier : {{ auth()->user()->name }}</span>
            <button type="button" onclick="window.toast('Client : Comptoir')" class="rounded-control px-2.5 py-2 text-[13px] font-medium uppercase hover:bg-subtle">
                Client
            </button>
        </div>
    </div>

    {{-- Corps : catégories / produits / panier --}}
    <div class="grid min-h-0 grid-cols-[88px_1fr] gap-3 p-3 lg:grid-cols-[190px_1fr_370px]">
        {{-- Catégories --}}
        <aside class="overflow-y-auto rounded-card bg-surface p-2 lg:p-3">
            @foreach ($categories as $category)
                <button
                    type="button"
                    wire:click="setCategory('{{ $category }}')"
                    class="mb-0.5 flex min-h-12 w-full items-center rounded-control px-2 text-center text-[10px] font-medium uppercase transition lg:px-3 lg:text-left lg:text-[12px]
                        {{ $activeCategory === $category ? 'bg-brand-500 font-bold text-white' : 'bg-subtle text-ink hover:bg-line' }}"
                >
                    {{ $category }}
                </button>
            @endforeach
        </aside>

        {{-- Produits --}}
        <section class="flex min-h-0 flex-col overflow-hidden rounded-card bg-surface p-3">
            <div class="mb-3 flex gap-2">
                <div class="relative flex-1">
                    <x-ui.icon name="search" :size="16" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-soft" />
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="search"
                        placeholder="Rechercher nom, SKU ou code-barres…"
                        class="h-11 w-full rounded-control border border-line-strong bg-surface pr-3 pl-10 text-sm text-ink focus:border-brand-500 focus:ring-3 focus:ring-brand-100 focus:outline-none"
                    >
                </div>
                <x-ui.button icon="scan-barcode" onclick="window.toast('Scanner prêt — F2')">Scanner</x-ui.button>
            </div>

            <p class="mb-2 text-[11px] text-soft">Produits disponibles • toucher une carte pour ajouter au panier</p>

            <div class="grid flex-1 grid-cols-2 content-start gap-1.5 overflow-y-auto pb-2 sm:grid-cols-3 xl:grid-cols-4">
                @forelse ($products as $product)
                    <button
                        type="button"
                        wire:click="addToCart('{{ $product['sku'] }}')"
                        wire:key="product-{{ $product['sku'] }}"
                        class="flex flex-col items-center rounded-card px-1 py-2 text-center transition hover:bg-subtle"
                    >
                        <span class="mb-1.5 line-clamp-2 min-h-7 text-[11px] leading-tight font-medium text-muted uppercase">
                            {{ $product['name'] }}
                        </span>

                        <span class="relative grid size-[72px] place-items-center rounded-full bg-gradient-to-br from-[#f1d08f] to-[#c8902e] text-3xl shadow-sm">
                            {{ $product['emoji'] }}
                            <span class="numeric absolute -right-1 -bottom-1 rounded-full bg-white px-1.5 py-0.5 text-[10px] font-bold text-ink shadow-sm">
                                {{ Format::money($product['price'], false) }}
                            </span>
                        </span>

                        <span @class([
                            'mt-1.5 text-[10px] font-medium',
                            'text-danger' => $product['stock'] < 10,
                            'text-warning' => $product['stock'] >= 10 && $product['stock'] < 25,
                            'text-brand-700' => $product['stock'] >= 25,
                        ])>
                            ● {{ $product['stock'] < 10 ? 'Stock critique' : ($product['stock'] < 25 ? 'Stock faible' : 'Disponible') }} · {{ $product['stock'] }}
                        </span>
                    </button>
                @empty
                    <div class="col-span-full">
                        <x-ui.empty-state title="Aucun produit" description="Essayez une autre recherche ou une autre catégorie." icon="package" />
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Panier --}}
        <aside class="hidden min-h-0 flex-col rounded-card bg-surface lg:flex">
            <div class="flex items-center justify-between border-b border-line px-4 py-3">
                <div>
                    <p class="text-sm font-bold text-ink">Panier</p>
                    <p class="text-[11px] text-soft">{{ now()->format('H:i') }}</p>
                </div>
                <x-ui.status-badge tone="success" :label="$itemCount.' article'.($itemCount > 1 ? 's' : '')" :icon="false" />
            </div>

            <div class="flex-1 overflow-y-auto">
                @forelse ($cart as $line)
                    <div class="border-b border-line">
                        <div class="flex items-center justify-between px-3 pt-3.5 pb-1.5">
                            <span class="text-[15px] font-medium uppercase">{{ $line['name'] }}</span>
                            <span class="numeric text-[15px] font-bold">{{ Format::money($line['price'] * $line['qty']) }}</span>
                        </div>

                        <div class="flex items-center gap-1.5 px-3 pb-2.5">
                            <button type="button" wire:click="changeQuantity('{{ $line['sku'] }}', -1)" class="grid size-8 place-items-center rounded-control border border-line-strong font-extrabold hover:bg-subtle">−</button>
                            <span class="numeric w-8 text-center text-sm font-bold">{{ $line['qty'] }}</span>
                            <button type="button" wire:click="changeQuantity('{{ $line['sku'] }}', 1)" class="grid size-8 place-items-center rounded-control border border-line-strong font-extrabold hover:bg-subtle">+</button>
                            <button type="button" wire:click="removeItem('{{ $line['sku'] }}')" class="ml-auto rounded-control border border-line-strong p-2 hover:bg-subtle" aria-label="Retirer">
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
                    class="min-h-14 w-full rounded-control bg-brand-700 text-[15px] font-bold tracking-wide text-white uppercase transition hover:bg-brand-800"
                >
                    Payer • {{ Format::money($subtotal) }}
                </button>
            </div>
        </aside>
    </div>

    {{-- Modale de paiement --}}
    @if ($paymentOpen)
        <div class="fixed inset-0 z-[80] flex items-center justify-center bg-ink/40 p-5">
            <div class="w-full max-w-lg rounded-modal border border-line bg-surface shadow-elevated">
                <div class="flex items-start justify-between border-b border-line px-4 py-4">
                    <div>
                        <p class="text-[15px] font-bold text-ink">Paiement</p>
                        <p class="text-[11px] text-soft">{{ $itemCount }} article(s)</p>
                    </div>
                    <button type="button" wire:click="closePayment" class="rounded-control p-1.5 text-muted hover:bg-subtle" aria-label="Fermer">
                        <x-ui.icon name="x" :size="18" />
                    </button>
                </div>

                <div class="p-4">
                    <div class="py-3 text-center">
                        <p class="text-[11px] font-bold tracking-wide text-soft uppercase">Total à payer</p>
                        <p class="numeric mt-1 text-4xl font-extrabold">{{ Format::money($subtotal) }}</p>
                    </div>

                    <div class="mt-2 grid grid-cols-2 gap-3">
                        @foreach ($paymentMethods as $key => $label)
                            <button
                                type="button"
                                wire:click="validatePayment('{{ $key }}')"
                                class="min-h-16 rounded-control border border-line-strong bg-surface px-3 text-sm font-bold uppercase transition hover:bg-subtle"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end border-t border-line px-4 py-3.5">
                    <x-ui.button variant="secondary" wire:click="closePayment">Annuler</x-ui.button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    window.addEventListener('notify', (event) => {
        if (window.toast) {
            window.toast(event.detail.message);
        }
    });
</script>
