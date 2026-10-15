@php
    use App\Support\Format;

    // Lien d'onglet : change la famille en conservant la recherche et la
    // catégorie ; le filtre de type est remis à zéro au changement d'onglet.
    $tabUrl = static fn (string $target): string => route('products.index', array_filter([
        'group' => $target,
        'search' => $search,
        'category' => $category,
    ], static fn (string $value): bool => $value !== ''));
@endphp

<x-layouts.app title="Produits & matières premières">
    <x-layout.page-header
        eyebrow="Opérations"
        title="Produits & matières premières"
        description="Le catalogue partagé entre tous les sites, scindé en deux familles : les produits et les matières premières."
    >
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" x-data @click="$dispatch('open-modal', 'new-product')">Nouveau produit</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <div
        class="mb-4 inline-flex flex-wrap gap-1 rounded-control border border-line bg-surface p-1"
        role="tablist"
        aria-label="Famille du catalogue"
    >
        @foreach ($groupOptions as $key => $label)
            @php $isActive = $key === $group; @endphp
            <a
                href="{{ $tabUrl((string) $key) }}"
                role="tab"
                aria-selected="{{ $isActive ? 'true' : 'false' }}"
                class="inline-flex items-center gap-2 rounded-control px-4 py-2 text-[13px] font-medium transition
                    {{ $isActive ? 'bg-brand-700 text-white' : 'text-muted hover:bg-subtle hover:text-ink' }}"
            >
                {{ $label }}
                <span class="numeric rounded-control px-1.5 text-[11px] {{ $isActive ? 'bg-brand-800 text-white' : 'bg-subtle text-muted' }}">
                    {{ Format::number($groupCounts[$key] ?? 0) }}
                </span>
            </a>
        @endforeach
    </div>

    <x-ui.card flush>
        <form method="GET" action="{{ route('products.index') }}" class="flex flex-wrap items-end gap-3 p-4">
            <input type="hidden" name="group" value="{{ $group }}">

            <div class="min-w-56 flex-1">
                <x-ui.search-input name="search" :value="$search" placeholder="Nom ou SKU…" />
            </div>

            @if (count($typeOptions) > 1)
                <div class="min-w-44">
                    <x-ui.select name="type" :options="$typeOptions" :selected="$type" placeholder="Tous les types" />
                </div>
            @endif

            <div class="min-w-44">
                <x-ui.select name="category" :options="$categoryOptions" :selected="$category" placeholder="Toutes les catégories" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>

            @if ($search !== '' || $type !== '' || $category !== '')
                <x-ui.button variant="ghost" :href="route('products.index', ['group' => $group])">Effacer</x-ui.button>
            @endif
        </form>

        @if ($products->isEmpty())
            <div class="p-4">
                <x-ui.empty-state
                    icon="package"
                    title="{{ $search !== '' || $type !== '' || $category !== '' ? 'Aucun résultat' : 'Aucun élément dans cette famille' }}"
                    description="Créez une matière première ou un produit fini pour commencer."
                >
                    <x-slot:action>
                        <x-ui.button variant="primary" icon="plus" x-data @click="$dispatch('open-modal', 'new-product')">Nouveau produit</x-ui.button>
                    </x-slot:action>
                </x-ui.empty-state>
            </div>
        @else
            <x-ui.data-table :headers="['Produit', 'Type', 'Catégorie', 'Unité', ['label' => 'Prix de vente', 'align' => 'right'], ['label' => 'Seuil', 'align' => 'right'], 'Statut', '']">
                @foreach ($products as $product)
                    <tr>
                        @php $photoUrl = $product->photoUrl(); @endphp
                        <td>
                            <span class="flex items-center gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-control border border-line bg-subtle">
                                    @if ($photoUrl !== null)
                                        <img src="{{ $photoUrl }}" alt="" class="size-full object-cover">
                                    @else
                                        <x-ui.icon name="package" :size="16" class="text-soft" />
                                    @endif
                                </span>

                                <span class="min-w-0">
                                    <span class="block font-bold text-ink">{{ $product->name }}</span>
                                    <span class="block text-[11px] text-soft">{{ $product->sku }}</span>
                                </span>
                            </span>
                        </td>
                        <td>{{ $product->type->label() }}</td>
                        <td>{{ $product->category?->name ?? '—' }}</td>
                        <td>{{ $product->primaryUnit?->symbol ?? '—' }}</td>
                        <td class="numeric text-right">{{ Format::money($product->sale_price) }}</td>
                        <td class="numeric text-right">{{ $product->alert_threshold ?? '—' }}</td>
                        <td>
                            <x-ui.status-badge
                                :tone="$product->is_active ? 'success' : 'neutral'"
                                :label="$product->is_active ? 'Actif' : 'Inactif'"
                                :icon="false"
                            />
                        </td>
                        <td>
                            <x-ui.button variant="secondary" size="sm" :href="route('products.edit', $product)">Modifier</x-ui.button>
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>

            <div class="p-4">
                {{ $products->links() }}
            </div>
        @endif
    </x-ui.card>

    <x-ui.modal name="new-product" title="Nouveau produit" subtitle="Dans quelle famille souhaitez-vous créer ?">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <a
                href="{{ route('products.create', ['group' => 'products']) }}"
                class="flex items-start gap-3 rounded-card border border-line bg-surface p-4 transition hover:border-brand-500 hover:bg-subtle focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
            >
                <span class="flex size-10 shrink-0 items-center justify-center rounded-control bg-brand-50 text-brand-700">
                    <x-ui.icon name="package" :size="20" />
                </span>
                <span class="min-w-0">
                    <span class="block font-bold text-ink">Produit</span>
                    <span class="block text-[13px] text-muted">Un produit fini, vendu en caisse ou au grossiste.</span>
                </span>
            </a>

            <a
                href="{{ route('products.create', ['group' => 'materials']) }}"
                class="flex items-start gap-3 rounded-card border border-line bg-surface p-4 transition hover:border-brand-500 hover:bg-subtle focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
            >
                <span class="flex size-10 shrink-0 items-center justify-center rounded-control bg-context-50 text-context-700">
                    <x-ui.icon name="boxes" :size="20" />
                </span>
                <span class="min-w-0">
                    <span class="block font-bold text-ink">Matière première</span>
                    <span class="block text-[13px] text-muted">Matières, consommables, emballages et ressources de production.</span>
                </span>
            </a>
        </div>
    </x-ui.modal>
</x-layouts.app>
