<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Inventory\StockMovementService;
use App\Enums\StockMovementType;
use App\Models\Bakery;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Stock de démonstration (local et testing uniquement).
 *
 * Le stock est amorcé par de véritables mouvements d'entrée fournisseur —
 * jamais par une écriture directe de `stocks.quantity` — ce qui garantit
 * la cohérence du grand livre. Idempotent : rejouer le seeder ne
 * duplique rien.
 */
class StockSeeder extends Seeder
{
    /** Quantité initiale par SKU (dans l'unité principale du produit). */
    private const INITIAL_STOCK = [
        'MP-FARINE' => 24,
        'MP-LEVURE' => 8,
        'MP-SEL' => 2,
        'MP-AMELIORANT' => 12,
        'PF-BAGUETTE' => 86,
        'PF-CROISSANT' => 18,
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $bakery = Bakery::query()->orderBy('id')->first();

        if ($bakery === null || Product::query()->count() === 0) {
            return;
        }

        $movements = app(StockMovementService::class);

        foreach (self::INITIAL_STOCK as $sku => $quantity) {
            $product = Product::query()->where('sku', $sku)->first();

            if ($product === null) {
                continue;
            }

            $movements->apply(
                bakery: $bakery,
                product: $product,
                type: StockMovementType::SupplierIn,
                quantity: (string) $quantity,
                reason: 'Stock initial de démonstration',
                reference: 'SEED-STOCK',
                idempotencyKey: "seed:stock:{$bakery->getKey()}:{$sku}",
            );
        }
    }
}
