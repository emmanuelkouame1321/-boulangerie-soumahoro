<?php

declare(strict_types=1);

namespace App\Domain\Production;

use App\Domain\Inventory\StockAlertService;
use App\Domain\Inventory\StockMovementService;
use App\Enums\BatchStatus;
use App\Enums\ProductionOrderStatus;
use App\Enums\RecipeDeductionMode;
use App\Enums\StockAlertType;
use App\Enums\StockMovementType;
use App\Exceptions\MissingGapReasonException;
use App\Exceptions\ProductionAlreadyValidatedException;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderItem;
use App\Models\Recipe;
use App\Models\User;
use App\Support\Decimal;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Production (cahier §7, §8, §27, §28 et §29).
 *
 * La validation d'une production suit le bloc transactionnel du cahier
 * §27 :
 *  1. vérifier le rendement et l'écart ;
 *  2. verrouiller l'ordre ;
 *  3. consommer les matières (sorties de stock) ;
 *  4. créer le lot et le calcul de sa date limite ;
 *  5. entrer les produits finis (entrée de stock) ;
 *  6. calculer le coût réel ;
 *  7. marquer l'ordre validé.
 *
 * Elle est **non rejouable** : un ordre déjà validé refuse une seconde
 * validation, et chaque mouvement porte une clé d'idempotence. Le stock
 * n'est jamais écrit directement : tout passe par `StockMovementService`.
 */
final class ProductionService
{
    public function __construct(
        private readonly StockMovementService $movements,
        private readonly RecipeCalculator $calculator,
        private readonly StockAlertService $alerts,
    ) {}

    /**
     * Crée un ordre de production et ses consommations planifiées, sans
     * effet sur le stock.
     */
    public function create(
        Bakery $bakery,
        Recipe $recipe,
        int|float|string $batches,
        ?User $user = null,
        ?CarbonInterface $producedAt = null,
        ?string $note = null,
        ProductionOrderStatus $status = ProductionOrderStatus::Pending,
    ): ProductionOrder {
        return DB::transaction(function () use (
            $bakery, $recipe, $batches, $user, $producedAt, $note, $status
        ): ProductionOrder {
            $batchCount = Decimal::abs($batches, Decimal::SCALE);

            $requirements = $this->calculator->requirements($recipe, $batchCount);
            $theoreticalQuantity = $this->calculator->theoreticalQuantity($recipe, $batchCount);
            $theoreticalCost = $this->calculator->theoreticalCost($recipe, $batchCount);

            $order = ProductionOrder::create([
                'bakery_id' => $bakery->getKey(),
                'recipe_id' => $recipe->getKey(),
                'created_by' => $user?->getKey(),
                'reference' => $this->nextOrderReference(),
                'status' => $status,
                'batch_count' => Decimal::trim($batchCount),
                'theoretical_quantity' => $theoreticalQuantity,
                'theoretical_cost' => $theoreticalCost,
                'produced_on' => ($producedAt ?? now())->toDateString(),
                'note' => $note,
            ]);

            foreach ($requirements as $requirement) {
                $order->items()->create([
                    'product_id' => $requirement['product_id'],
                    'unit_id' => $requirement['unit_id'],
                    'deduction_mode' => $requirement['deduction_mode'],
                    'planned_quantity' => $requirement['planned_quantity'],
                    'unit_cost' => $requirement['unit_cost'],
                    'line_cost' => $requirement['line_cost'],
                    'position' => $requirement['position'],
                ]);
            }

            return $order->refresh();
        });
    }

    /**
     * Valide un ordre : consomme les matières, crée le lot et le produit
     * fini, calcule le coût réel et l'écart. Refuse une seconde validation.
     *
     * @param  array<int, int|float|string>  $consumptions  quantités réelles par ligne
     */
    public function validate(
        ProductionOrder $order,
        int|float|string $actualQuantity,
        ?string $gapReason = null,
        ?User $user = null,
        array $consumptions = [],
    ): ProductionOrder {
        return DB::transaction(function () use ($order, $actualQuantity, $gapReason, $user, $consumptions): ProductionOrder {
            $locked = ProductionOrder::withoutBakeryScope()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->status->canValidate()) {
                throw ProductionAlreadyValidatedException::for($locked);
            }

            $bakery = $locked->bakery()->firstOrFail();

            $theoretical = Decimal::trim((string) $locked->theoretical_quantity);
            $actual = Decimal::abs($actualQuantity, Decimal::SCALE);

            // À défaut de quantité saisie, on retient le rendement théorique.
            if (! Decimal::isPositive($actual)) {
                $actual = $theoretical;
            }

            $gap = Decimal::trim($this->calculator->gap($theoretical, $actual));

            $reason = $gapReason !== null ? trim($gapReason) : '';

            if (bccomp($gap, '0', Decimal::SCALE) !== 0
                && (bool) config('boulangerie.guards.require_production_gap_reason', true)
                && $reason === '') {
                throw MissingGapReasonException::for($locked, Decimal::trim($gap));
            }

            $actualCost = 0;

            foreach ($locked->items()->get() as $item) {
                $actualCost += $this->consumeItem($locked, $item, $bakery, $user, $consumptions);
            }

            $this->recordOutput($locked, $bakery, $actual, $actualCost, $user);

            $locked->status = ProductionOrderStatus::Validated;
            $locked->actual_quantity = $actual;
            $locked->gap = Decimal::trim($gap);
            $locked->gap_reason = bccomp($gap, '0', Decimal::SCALE) === 0 ? null : $reason;
            $locked->actual_cost = $actualCost;
            $locked->validated_at = now();
            $locked->validated_by = $user?->getKey();
            $locked->save();

            if (bccomp($gap, '0', Decimal::SCALE) !== 0) {
                $this->openGapAlerts($locked, $bakery, $gap);
            }

            return $locked;
        });
    }

    /**
     * Alerte « écart de rendement » (cahier §22) : événement ouvert sur
     * le produit sorti, refermé par acquittement.
     */
    private function openGapAlerts(ProductionOrder $order, Bakery $bakery, string $gap): void
    {
        foreach ($order->outputs()->pluck('product_id')->unique() as $productId) {
            $product = Product::query()->find((int) $productId);

            if ($product === null) {
                continue;
            }

            $this->alerts->open(
                $bakery,
                $product,
                StockAlertType::ProductionGap,
                sprintf(
                    'Écart de rendement sur %s : %s unités (%s).',
                    $order->reference,
                    Decimal::trim($gap),
                    $order->gap_reason ?? 'sans motif'
                ),
                Decimal::abs($gap),
            );
        }
    }

    /**
     * Crée puis valide immédiatement un ordre (parcours assistant écran).
     *
     * @param  array<int, int|float|string>  $consumptions
     */
    public function produce(
        Bakery $bakery,
        Recipe $recipe,
        int|float|string $batches,
        int|float|string $actualQuantity,
        ?string $gapReason = null,
        ?User $user = null,
        ?CarbonInterface $producedAt = null,
        ?string $note = null,
        array $consumptions = [],
    ): ProductionOrder {
        // Une seule transaction : si la validation échoue (matières
        // insuffisantes, écart non motivé), l'ordre créé est annulé et rien
        // de partiellement validé ne subsiste (cahier §27).
        return DB::transaction(function () use (
            $bakery, $recipe, $batches, $actualQuantity, $gapReason, $user, $producedAt, $note, $consumptions
        ): ProductionOrder {
            $order = $this->create($bakery, $recipe, $batches, $user, $producedAt, $note);

            return $this->validate($order, $actualQuantity, $gapReason, $user, $consumptions);
        });
    }

    /**
     * Consomme une ressource : sortie de stock si la ressource est
     * physique, coût sinon. Retourne le coût de la ligne en FCFA.
     *
     * @param  array<int, int|float|string>  $consumptions
     */
    private function consumeItem(
        ProductionOrder $order,
        ProductionOrderItem $item,
        Bakery $bakery,
        ?User $user,
        array $consumptions,
    ): int {
        $consumed = $this->consumedQuantity($item, $consumptions);

        if ($item->deduction_mode->deductsStock() && Decimal::isPositive($consumed)) {
            $this->movements->apply(
                bakery: $bakery,
                product: $item->product()->firstOrFail(),
                type: StockMovementType::ProductionOut,
                quantity: $consumed,
                unit: $item->unit,
                user: $user,
                idempotencyKey: "production:{$order->getKey()}:item:{$item->getKey()}",
                reference: $order->reference,
                reason: 'Consommation de production',
                source: $order,
                occurredAt: $order->produced_on,
            );
        }

        $lineCost = RecipeCalculator::lineCost($consumed, (int) $item->unit_cost);

        $item->actual_quantity = $consumed;
        $item->line_cost = $lineCost;
        $item->save();

        return $lineCost;
    }

    /**
     * Crée le lot, la sortie de produit fini et l'entrée de stock
     * correspondante (cahier §7.3 et §8).
     */
    private function recordOutput(
        ProductionOrder $order,
        Bakery $bakery,
        string $actualQuantity,
        int $actualCost,
        ?User $user,
    ): void {
        $recipe = $order->recipe()->first();

        if ($recipe === null) {
            return;
        }

        $product = $recipe->product()->firstOrFail();
        $producedOn = $order->produced_on;
        $expiresOn = $this->expiryDate($recipe, $product->shelf_life_days, $producedOn);

        $output = $order->outputs()->create([
            'product_id' => $product->getKey(),
            'unit_id' => $product->primary_unit_id,
            'quantity' => $actualQuantity,
            'unit_cost' => $this->unitCostFromTotal($actualCost, $actualQuantity),
            'produced_on' => $producedOn->toDateString(),
        ]);

        $batch = ProductionBatch::create([
            'bakery_id' => $bakery->getKey(),
            'production_order_id' => $order->getKey(),
            'production_output_id' => $output->getKey(),
            'product_id' => $product->getKey(),
            'reference' => $this->nextBatchReference(),
            'initial_quantity' => $actualQuantity,
            'available_quantity' => $actualQuantity,
            'sold_quantity' => 0,
            'lost_quantity' => 0,
            'produced_on' => $producedOn->toDateString(),
            'expires_on' => $expiresOn?->toDateString(),
            'status' => BatchStatus::Fresh,
        ]);

        if (Decimal::isPositive($actualQuantity)) {
            $this->movements->apply(
                bakery: $bakery,
                product: $product,
                type: StockMovementType::ProductionIn,
                quantity: $actualQuantity,
                unit: $product->primaryUnit,
                user: $user,
                idempotencyKey: "production:{$order->getKey()}:output",
                reference: $order->reference,
                reason: 'Entrée de produits finis',
                comment: "Lot {$batch->reference}",
                source: $order,
                occurredAt: $producedOn,
            );
        }
    }

    /**
     * Quantité consommée : réelle saisie si fournie, sinon planifiée. Le
     * mode « saisie » consomme la quantité réelle enregistrée sur la ligne.
     *
     * @param  array<int, int|float|string>  $consumptions
     */
    private function consumedQuantity(ProductionOrderItem $item, array $consumptions): string
    {
        if (array_key_exists($item->getKey(), $consumptions)) {
            return Decimal::abs($consumptions[$item->getKey()], Decimal::SCALE);
        }

        if ($item->deduction_mode === RecipeDeductionMode::Manual && $item->actual_quantity !== null) {
            return Decimal::trim((string) $item->actual_quantity);
        }

        return Decimal::trim((string) $item->planned_quantity);
    }

    /**
     * Coût de revient unitaire du produit fini, en entiers FCFA.
     */
    private function unitCostFromTotal(int $total, string $quantity): int
    {
        if (! Decimal::isPositive($quantity) || $total <= 0) {
            return 0;
        }

        return (int) bcadd(bcdiv((string) $total, $quantity, 2), '0.5', 0);
    }

    /**
     * Date limite de vente : durée de la recette, sinon du produit, sinon
     * valeur par défaut (cahier §8.1).
     */
    private function expiryDate(Recipe $recipe, ?int $productShelfLife, CarbonInterface $producedOn): ?CarbonInterface
    {
        $days = $recipe->shelf_life_days ?? $productShelfLife ?? config('boulangerie.production.default_shelf_life_days');

        if ($days === null) {
            return null;
        }

        return $producedOn->copy()->addDays((int) $days);
    }

    private function nextOrderReference(): string
    {
        $prefix = 'PRD-'.now()->format('Ym').'-';

        $sequence = ProductionOrder::withoutBakeryScope()
            ->where('reference', 'like', "{$prefix}%")
            ->count() + 1;

        do {
            $reference = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (ProductionOrder::withoutBakeryScope()->where('reference', $reference)->exists());

        return $reference;
    }

    private function nextBatchReference(): string
    {
        $prefix = 'LOT-'.now()->format('Ym').'-';

        $sequence = ProductionBatch::withoutBakeryScope()
            ->where('reference', 'like', "{$prefix}%")
            ->count() + 1;

        do {
            $reference = $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $sequence++;
        } while (ProductionBatch::withoutBakeryScope()->where('reference', $reference)->exists());

        return $reference;
    }
}
