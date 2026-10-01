<?php

declare(strict_types=1);

namespace App\Livewire\Sales;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Caisse (POS) — écran plein de la charte caisse.
 *
 * Cette étape livre l'interface de caisse conforme au prototype. La
 * persistance et les règles de vente (déduction du stock produits finis,
 * idempotence, clôture journalière) appartiennent au domaine Ventes
 * (`App\Domain\Sales`, Phase 5). Le catalogue ci-dessous est un jeu de
 * démonstration en attendant le modèle `Product` (Phase 1.4).
 */
final class PointOfSale extends Component
{
    /**
     * Catalogue de démonstration (remplacé par le modèle Product en Phase 1.4).
     *
     * @var list<array{sku: string, name: string, description: string, price: int, stock: int, category: string, emoji: string}>
     */
    private const CATALOG = [
        ['sku' => 'SKU-BAGUETTE', 'name' => 'Baguette', 'description' => 'Pain traditionnel', 'price' => 500, 'stock' => 86, 'category' => 'Pains', 'emoji' => '🥖'],
        ['sku' => 'SKU-PAIN-COMPLET', 'name' => 'Pain complet', 'description' => 'Farine complète', 'price' => 700, 'stock' => 34, 'category' => 'Pains', 'emoji' => '🍞'],
        ['sku' => 'SKU-CROISSANT', 'name' => 'Croissant', 'description' => 'Beurre', 'price' => 800, 'stock' => 42, 'category' => 'Viennoiseries', 'emoji' => '🥐'],
        ['sku' => 'SKU-PAIN-CHOCO', 'name' => 'Pain au chocolat', 'description' => 'Chocolat', 'price' => 900, 'stock' => 18, 'category' => 'Viennoiseries', 'emoji' => '🥐'],
        ['sku' => 'SKU-CAKE-NATURE', 'name' => 'Cake nature', 'description' => 'Format familial', 'price' => 3500, 'stock' => 12, 'category' => 'Pâtisserie', 'emoji' => '🍰'],
        ['sku' => 'SKU-GATEAU-CHOCO', 'name' => 'Gâteau chocolat', 'description' => 'Part 6 personnes', 'price' => 12000, 'stock' => 7, 'category' => 'Pâtisserie', 'emoji' => '🍰'],
        ['sku' => 'SKU-SANDWICH', 'name' => 'Sandwich poulet', 'description' => 'Poulet & crudités', 'price' => 2500, 'stock' => 16, 'category' => 'Sandwichs', 'emoji' => '🥪'],
        ['sku' => 'SKU-PIZZA', 'name' => 'Pizza individuelle', 'description' => 'Tomate & fromage', 'price' => 2500, 'stock' => 9, 'category' => 'Plats', 'emoji' => '🍕'],
        ['sku' => 'SKU-EAU-50', 'name' => 'Eau 50cl', 'description' => 'Boisson', 'price' => 500, 'stock' => 112, 'category' => 'Boissons', 'emoji' => '💧'],
        ['sku' => 'SKU-JUS-ORANGE', 'name' => 'Jus naturel', 'description' => 'Orange', 'price' => 1000, 'stock' => 27, 'category' => 'Boissons', 'emoji' => '🧃'],
    ];

    public string $search = '';

    public string $activeCategory = 'Toutes';

    /** @var list<array{sku: string, name: string, price: int, qty: int}> */
    public array $cart = [];

    public bool $paymentOpen = false;

    public function setCategory(string $category): void
    {
        $this->activeCategory = $category;
    }

    public function addToCart(string $sku): void
    {
        $product = $this->findProduct($sku);

        if ($product === null) {
            return;
        }

        foreach ($this->cart as $index => $line) {
            if ($line['sku'] === $sku) {
                if ($line['qty'] >= $product['stock']) {
                    $this->dispatch('notify', message: 'Stock insuffisant pour « '.$product['name'].' »');

                    return;
                }

                $this->cart[$index]['qty']++;
                $this->dispatch('notify', message: $product['name'].' ajouté au panier');

                return;
            }
        }

        $this->cart[] = [
            'sku' => $sku,
            'name' => $product['name'],
            'price' => $product['price'],
            'qty' => 1,
        ];

        $this->dispatch('notify', message: $product['name'].' ajouté au panier');
    }

    public function changeQuantity(string $sku, int $delta): void
    {
        foreach ($this->cart as $index => $line) {
            if ($line['sku'] !== $sku) {
                continue;
            }

            $quantity = $line['qty'] + $delta;

            if ($quantity <= 0) {
                $this->removeItem($sku);

                return;
            }

            $this->cart[$index]['qty'] = $quantity;

            return;
        }
    }

    public function removeItem(string $sku): void
    {
        $this->cart = array_values(array_filter(
            $this->cart,
            static fn (array $line): bool => $line['sku'] !== $sku
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

    public function validatePayment(string $method): void
    {
        if (! array_key_exists($method, config('boulangerie.payment_methods', []))) {
            return;
        }

        if ($this->cart === []) {
            $this->paymentOpen = false;

            return;
        }

        // TODO(Phase 5) : appeler App\Domain\Sales\SalesService::record() —
        // déduction du stock produits finis, idempotence, clôture journalière.
        $reference = 'VTE-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));

        $this->cart = [];
        $this->paymentOpen = false;

        $this->dispatch('notify', message: 'Vente '.$reference.' validée');
    }

    public function render(): View
    {
        return view('livewire.sales.point-of-sale', [
            'categories' => $this->categories(),
            'products' => $this->filteredProducts(),
            'subtotal' => $this->subtotal(),
            'itemCount' => $this->itemCount(),
            'paymentMethods' => config('boulangerie.payment_methods', []),
        ]);
    }

    /**
     * @return list<string>
     */
    private function categories(): array
    {
        $categories = ['Toutes'];

        foreach (self::CATALOG as $product) {
            if (! in_array($product['category'], $categories, true)) {
                $categories[] = $product['category'];
            }
        }

        return $categories;
    }

    /**
     * @return list<array{sku: string, name: string, description: string, price: int, stock: int, category: string, emoji: string}>
     */
    private function filteredProducts(): array
    {
        $needle = mb_strtolower(trim($this->search));

        return array_values(array_filter(
            self::CATALOG,
            function (array $product) use ($needle): bool {
                if ($this->activeCategory !== 'Toutes' && $product['category'] !== $this->activeCategory) {
                    return false;
                }

                if ($needle === '') {
                    return true;
                }

                return str_contains(mb_strtolower($product['name'].' '.$product['sku'].' '.$product['description']), $needle);
            }
        ));
    }

    /**
     * @return array{sku: string, name: string, description: string, price: int, stock: int, category: string, emoji: string}|null
     */
    private function findProduct(string $sku): ?array
    {
        foreach (self::CATALOG as $product) {
            if ($product['sku'] === $sku) {
                return $product;
            }
        }

        return null;
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
