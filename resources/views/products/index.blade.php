@php
    use App\Support\Format;
@endphp

<x-layouts.app title="Produits & matières premières">
    <x-layout.page-header
        eyebrow="Opérations"
        title="Produits & matières premières"
        description="Le catalogue partagé entre tous les sites. Le stock, lui, reste propre à chaque boulangerie."
    >
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" :href="route('products.create')">Nouveau produit</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('status'))
        <x-ui.alert tone="success" class="mb-4">{{ session('status') }}</x-ui.alert>
    @endif

    <x-ui.card flush>
        <form method="GET" action="{{ route('products.index') }}" class="flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-ui.search-input name="search" :value="$search" placeholder="Nom ou SKU…" />
            </div>

            <div class="min-w-44">
                <x-ui.select name="type" :options="$typeOptions" :selected="$type" placeholder="Tous les types" />
            </div>

            <div class="min-w-44">
                <x-ui.select name="category" :options="$categoryOptions" :selected="$category" placeholder="Toutes les catégories" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filtrer</x-ui.button>

            @if ($search !== '' || $type !== '' || $category !== '')
                <x-ui.button variant="ghost" :href="route('products.index')">Effacer</x-ui.button>
            @endif
        </form>

        @if ($products->isEmpty())
            <div class="p-4">
                <x-ui.empty-state
                    icon="package"
                    title="{{ $search !== '' || $type !== '' || $category !== '' ? 'Aucun résultat' : 'Aucun produit' }}"
                    description="Créez une matière première ou un produit fini pour commencer."
                >
                    <x-slot:action>
                        <x-ui.button variant="primary" icon="plus" :href="route('products.create')">Nouveau produit</x-ui.button>
                    </x-slot:action>
                </x-ui.empty-state>
            </div>
        @else
            <x-ui.data-table :headers="['Produit', 'Type', 'Catégorie', 'Unité', ['label' => 'Prix de vente', 'align' => 'right'], ['label' => 'Seuil', 'align' => 'right'], 'Statut', '']">
                @foreach ($products as $product)
                    <tr>
                        <td>
                            <span class="block font-bold text-ink">{{ $product->name }}</span>
                            <span class="block text-[11px] text-soft">{{ $product->sku }}</span>
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
</x-layouts.app>
