@php
    use App\Support\Format;
@endphp

<div class="grid h-screen grid-rows-[60px_1fr]" x-data="{ cartOpen: false }">
    {{-- En-tête de caisse --}}
    <div class="flex min-w-0 items-center gap-2 border-b border-line bg-surface px-3">
        <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2 rounded-control px-2 py-2 text-ink transition hover:bg-subtle">
            <x-ui.icon name="arrow-left" :size="18" />
            <span class="hidden text-[13px] font-medium uppercase sm:inline">Quitter</span>
        </a>

        <x-ui.status-badge tone="success" label="Caisse active" :icon="false" class="shrink-0" />

        <div class="ml-2 hidden items-center gap-2 md:flex">
            @foreach ($paymentMethods as $key => $label)
                <span class="rounded-control border border-context-600 px-3 py-1.5 text-[11px] font-medium tracking-wide text-context-600 uppercase">
                    {{ $label }}
                </span>
            @endforeach
        </div>

        <div class="ml-auto flex min-w-0 items-center gap-2">
            <span class="hidden sm:inline-flex">
                <x-ui.status-badge tone="info" :label="app(\App\Domain\Sites\BakeryContext::class)->current()?->name ?? 'Toutes les boulangeries'" :icon="false" />
            </span>
            <span class="hidden text-[11px] text-soft md:inline">Caissier : {{ auth()->user()->name }}</span>
            <button type="button" onclick="window.toast('Client : Comptoir')" class="shrink-0 rounded-control px-2.5 py-2 text-[13px] font-medium uppercase hover:bg-subtle">
                Client
            </button>
        </div>
    </div>

    {{-- Corps : catégories / produits (panier en colonne sur grand écran) --}}
    <div class="grid min-h-0 grid-cols-1 grid-rows-[auto_1fr] gap-3 px-3 pt-3 pb-24 lg:grid-cols-[190px_1fr_370px] lg:grid-rows-1 lg:p-3">
        {{-- Catégories : défilement horizontal sur mobile, colonne sur grand écran --}}
        <aside class="flex gap-1.5 overflow-x-auto rounded-card bg-surface p-2 lg:block lg:overflow-y-auto lg:p-3">
            @foreach ($categories as $category)
                <button
                    type="button"
                    wire:click="setCategory('{{ $category }}')"
                    wire:key="category-{{ $category }}"
                    class="flex min-h-11 shrink-0 items-center justify-center rounded-control px-3 text-[11px] font-medium whitespace-nowrap uppercase transition lg:mb-0.5 lg:min-h-12 lg:w-full lg:shrink lg:justify-start lg:text-[12px]
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
                <span class="hidden shrink-0 sm:inline-flex">
                    <x-ui.button icon="scan-barcode" onclick="window.toast('Scanner prêt — F2')">Scanner</x-ui.button>
                </span>
            </div>

            <p class="mb-2 text-[11px] text-soft">Produits disponibles • toucher une carte pour ajouter au panier</p>

            <div class="grid flex-1 grid-cols-2 content-start gap-2 overflow-y-auto pb-2 sm:grid-cols-3 xl:grid-cols-4">
                @forelse ($products as $product)
                    <button
                        type="button"
                        wire:click="addToCart({{ $product['id'] }})"
                        wire:key="product-{{ $product['id'] }}"
                        class="group flex flex-col overflow-hidden rounded-card border border-line bg-surface text-left shadow-card transition hover:border-brand-500 hover:shadow-elevated"
                    >
                        {{-- Visuel : photo plein cadre, sinon emoji de la famille --}}
                        <span class="relative flex aspect-square w-full items-center justify-center overflow-hidden bg-gradient-to-br from-crust-100 to-crust-500">
                            @if (! empty($product['photo']))
                                <img
                                    src="{{ $product['photo'] }}"
                                    alt=""
                                    loading="lazy"
                                    class="size-full object-cover transition duration-300 group-hover:scale-105"
                                >
                            @else
                                <span class="text-4xl">{{ $product['emoji'] }}</span>
                            @endif
                        </span>

                        {{-- Nom, prix et état du stock, dans un corps aéré --}}
                        <span class="flex flex-1 flex-col gap-1.5 p-2">
                            <span class="line-clamp-2 min-h-8 text-[12px] leading-tight font-semibold text-ink">
                                {{ $product['name'] }}
                            </span>

                            <span class="mt-auto flex items-center justify-between gap-1.5">
                                <span class="numeric text-[13px] leading-none font-bold text-brand-700">
                                    {{ Format::money($product['price'], false) }}
                                </span>

                                <span @class([
                                    'rounded-full px-1.5 py-0.5 text-[10px] leading-none font-medium',
                                    'bg-danger-bg text-danger' => $product['stock'] < 10,
                                    'bg-warning-bg text-warning' => $product['stock'] >= 10 && $product['stock'] < 25,
                                    'bg-brand-50 text-brand-700' => $product['stock'] >= 25,
                                ])>
                                    {{ $product['stock'] < 10 ? 'Critique' : ($product['stock'] < 25 ? 'Faible' : 'Dispo') }} · {{ $product['stock'] }}
                                </span>
                            </span>
                        </span>
                    </button>
                @empty
                    <div class="col-span-full">
                        <x-ui.empty-state title="Aucun produit" description="Essayez une autre recherche ou une autre catégorie." icon="package" />
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Panier : colonne sur grand écran uniquement --}}
        <aside class="hidden min-h-0 flex-col rounded-card bg-surface lg:flex">
            @include('livewire.sales.partials.cart', ['drawer' => false, 'scope' => 'desktop'])
        </aside>
    </div>

    {{-- Barre d'action mobile : toujours visible en bas --}}
    <div class="fixed inset-x-0 bottom-0 z-[60] border-t border-line bg-surface px-3 py-2.5 lg:hidden">
        <div class="flex items-center gap-2">
            <div class="min-w-0 flex-1">
                <p class="text-[11px] text-soft">{{ $itemCount }} article{{ $itemCount > 1 ? 's' : '' }}</p>
                <p class="numeric truncate text-lg leading-tight font-bold text-ink">{{ Format::money($subtotal) }}</p>
            </div>

            <button
                type="button"
                x-on:click="cartOpen = true"
                class="flex min-h-12 shrink-0 items-center gap-2 rounded-control border border-line-strong px-4 text-[13px] font-bold uppercase"
            >
                <x-ui.icon name="shopping-bag" :size="16" />
                Panier
                @if ($itemCount > 0)
                    <span class="numeric grid size-5 place-items-center rounded-full bg-brand-700 text-[10px] font-bold text-white">{{ $itemCount }}</span>
                @endif
            </button>

            <button
                type="button"
                wire:click="openPayment"
                class="min-h-12 shrink-0 rounded-control bg-brand-700 px-4 text-[13px] font-bold uppercase text-white"
            >
                Payer
            </button>
        </div>
    </div>

    {{-- Panier mobile : tiroir bas --}}
    <div x-show="cartOpen" x-cloak class="fixed inset-0 z-[80] lg:hidden" role="dialog" aria-modal="true" aria-label="Panier">
        <div class="absolute inset-0 bg-ink/40" x-on:click="cartOpen = false" aria-hidden="true"></div>

        <div class="absolute inset-x-0 bottom-0 flex max-h-[85vh] min-h-[50vh] flex-col rounded-t-modal border-t border-line bg-surface shadow-elevated">
            @include('livewire.sales.partials.cart', ['drawer' => true, 'scope' => 'mobile'])
        </div>
    </div>

    {{-- Modale de paiement --}}
    @if ($paymentOpen)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-ink/40 p-5">
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
