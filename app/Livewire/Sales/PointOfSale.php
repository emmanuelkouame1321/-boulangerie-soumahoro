<?php

declare(strict_types=1);

namespace App\Livewire\Sales;

use App\Domain\Inventory\StockService;
use App\Domain\Sales\SalesService;
use App\Domain\Sites\BakeryContext;
use App\Enums\ProductType;
use App\Exceptions\EmptySaleException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\LotExpiredException;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

/**
 * Caisse (POS) — ventes au comptoir (cahier §14).
 *
 * Le composant porte l'état du panier et appelle `SalesService` : la règle
 * de vente (déduction du stock produits finis, idempotence) reste dans le
 * domaine. Le catalogue provient des produits finis du site courant.
 */
final class PointOfSale extends Component
{
    public string $search = '';

    public string $activeCategory = 'Toutes';

    /** @var list<array{product_id: int, sku: string, name: string, price: int, qty: int}> */
    public array $cart = [];

    public bool $paymentOpen = false;

    public function setCategory(string $category): void
    {
        $this->activeCategory = $category;
    }

    public function addToCart(int $productId): void
    {
        $product = $this->catalog()->firstWhere('id', $productId);

        if ($product === null) {
            return;
        }

        $available = $this->availableFor($product);

        foreach ($this->cart as $index => $line) {
            if ($line['product_id'] === $productId) {
                if ($line['qty'] >= $available) {
                    $this->dispatch('notify', message: 'Stock insuffisant pour « '.$product->name.' »');

                    return;
                }

                $this->cart[$index]['qty']++;
                $this->dispatch('notify', message: $product->name.' ajouté au panier');

                return;
            }
        }

        if ($available <= 0) {
            $this->dispatch('notify', message: 'Stock épuisé pour « '.$product->name.' »');

            return;
        }

        $this->cart[] = [
            'product_id' => $product->getKey(),
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => (int) $product->sale_price,
            'qty' => 1,
        ];

        $this->dispatch('notify', message: $product->name.' ajouté au panier');
    }

    public function changeQuantity(int $productId, int $delta): void
    {
        foreach ($this->cart as $index => $line) {
            if ($line['product_id'] !== $productId) {
                continue;
            }

            $quantity = $line['qty'] + $delta;

            if ($quantity <= 0) {
                $this->removeItem($productId);

                return;
            }

            $this->cart[$index]['qty'] = $quantity;

            return;
        }
    }

    public function removeItem(int $productId): void
    {
        $this->cart = array_values(array_filter(
            $this->cart,
            static fn (array $line): bool => $line['product_id'] !== $productId
        ));
    }

    public function openPayment(): void
    {
        if ($this->cart === []) {
            $this->dispatch('notify', message: 'Ajoutez au moins un article');

            return;
        }

        $this->paymentOpen = true;
    }

    public function closePayment(): void
    {
        $this->paymentOpen = false;
    }

    public function validatePayment(string $method, SalesService $sales, BakeryContext $context): void
    {
        if (! array_key_exists($method, config('boulangerie.payment_methods', []))) {
            return;
        }

        $bakery = $this->resolveBakery($context);

        if ($bakery === null) {
            $this->dispatch('notify', message: 'Aucun site actif pour encaisser');

            return;
        }

        if ($this->cart === []) {
            $this->paymentOpen = false;

            return;
        }

        $lines = array_map(static fn (array $line): array => [
            'product_id' => $line['product_id'],
            'quantity' => $line['qty'],
            'unit_price' => $line['price'],
        ], $this->cart);

        try {
            $sale = $sales->record(
                bakery: $bakery,
                lines: $lines,
                paymentMethod: $method,
                user: auth()->user() instanceof User ? auth()->user() : null,
            );
        } catch (InsufficientStockException|EmptySaleException|LotExpiredException $exception) {
            $this->dispatch('notify', message: $exception->getMessage());

            return;
        }

        $this->cart = [];
        $this->paymentOpen = false;

        $this->dispatch('notify', message: 'Vente '.$sale->reference.' validée');
    }

    public function render(): View
    {
        $products = $this->filteredProducts();

        return view('livewire.sales.point-of-sale', [
            'categories' => $this->categories(),
            'products' => $products,
            'subtotal' => $this->subtotal(),
            'itemCount' => $this->itemCount(),
            'paymentMethods' => config('boulangerie.payment_methods', []),
        ]);
    }

    /**
     * @return Collection<int, Product>
     */
    private function catalog(): Collection
    {
        return Product::query()
            ->active()
            ->where('type', ProductType::FinishedGood->value)
            ->with('category')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return list<array{id: int, sku: string, name: string, description: string, price: int, stock: int, category: string, emoji: string, photo: string|null}>
     */
    private function filteredProducts(): array
    {
        $needle = mb_strtolower(trim($this->search));
        $bakery = $this->resolveBakery(app(BakeryContext::class));

        $rows = [];

        foreach ($this->catalog() as $product) {
            $category = $this->categoryName($product);

            if ($this->activeCategory !== 'Toutes' && $category !== $this->activeCategory) {
                continue;
            }

            if ($needle !== '' && ! str_contains(mb_strtolower($product->name.' '.$product->sku), $needle)) {
                continue;
            }

            $rows[] = [
                'id' => (int) $product->getKey(),
                'sku' => (string) $product->sku,
                'name' => (string) $product->name,
                'description' => (string) ($product->description ?? ''),
                'price' => (int) $product->sale_price,
                'stock' => $bakery !== null ? (int) round((float) app(StockService::class)->physical($product, $bakery)) : 0,
                'category' => $category,
                'emoji' => $this->emoji($product),
                'photo' => $product->photoUrl(),
            ];
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function categories(): array
    {
        $categories = ['Toutes'];

        foreach ($this->catalog() as $product) {
            $category = $this->categoryName($product);

            if (! in_array($category, $categories, true)) {
                $categories[] = $category;
            }
        }

        return $categories;
    }

    private function categoryName(Product $product): string
    {
        $name = data_get($product, 'category.name');

        return is_string($name) && $name !== '' ? $name : 'Autres';
    }

    private function emoji(Product $product): string
    {
        return match (data_get($product, 'category.code')) {
            'bread' => '🥖',
            'pastry' => '🥐',
            'cake' => '🍰',
            'drink' => '🧃',
            'sandwich' => '🥪',
            default => '🍞',
        };
    }

    private function availableFor(Product $product): int
    {
        $bakery = $this->resolveBakery(app(BakeryContext::class));

        if ($bakery === null) {
            return 0;
        }

        return (int) round((float) app(StockService::class)->physical($product, $bakery));
    }

    private function resolveBakery(BakeryContext $context): ?Bakery
    {
        return $context->current() ?? $context->availableBakeries()->first();
    }

    private function subtotal(): int
    {
        $total = 0;

        foreach ($this->cart as $line) {
            $total += $line['price'] * $line['qty'];
        }

        return $total;
    }

    private function itemCount(): int
    {
        $count = 0;

        foreach ($this->cart as $line) {
            $count += $line['qty'];
        }

        return $count;
    }
}
