<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

use App\Enums\BatchStatus;
use App\Enums\LossOrigin;
use App\Enums\LossReason;
use App\Enums\StockAlertStatus;
use App\Enums\StockAlertType;
use App\Models\Bakery;
use App\Models\LossRecord;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\StockAlert;
use App\Models\User;
use App\Support\Decimal;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Péremption (cahier §8, §8.2, §22 et §40).
 *
 *  - `refresh()`   : dérive les statuts des lots (frais / à écouler /
 *                    expiré / épuisé) et ouvre ou referme les alertes
 *                    « date limite proche » et « produit expiré » ;
 *  - `convertExpired()` : transforme le restant des lots expirés en
 *                    perte, idempotente (cahier §29), en enregistrant la
 *                    valeur de la perte et en conservant le lot.
 *
 * La règle de conversion est configurable (`boulangerie.expiry.conversion`)
 * : à l'arrivée de la date limite (défaut, §8.2) ou le jour suivant.
 */
final class ExpiryService
{
    public function __construct(
        private readonly LotService $lots,
        private readonly LossService $losses,
        private readonly StockAlertService $alerts,
    ) {}

    /**
     * Lots dont la date limite est atteinte (règle configurée) et qui
     * restent à traiter. Partagé avec l'écran Pertes.
     *
     * @return Builder<ProductionBatch>
     */
    public static function expiredLotsQuery(): Builder
    {
        $today = now()->toDateString();
        $rule = (string) config('boulangerie.expiry.conversion', 'on_expiry_date');

        return ProductionBatch::query()
            ->where('available_quantity', '>', 0)
            ->whereNotNull('expires_on')
            ->whereDate('expires_on', $rule === 'day_after_expiry_date' ? '<' : '<=', $today);
    }

    /**
     * La date limite du lot est-elle atteinte à cette date ?
     */
    public static function isExpiredOn(ProductionBatch $batch, CarbonInterface $date): bool
    {
        if ($batch->expires_on === null) {
            return false;
        }

        $today = $date->copy()->startOfDay();
        $rule = (string) config('boulangerie.expiry.conversion', 'on_expiry_date');

        return $rule === 'day_after_expiry_date'
            ? $batch->expires_on->lt($today)
            : $batch->expires_on->lte($today);
    }

    /**
     * Signale les lots à écouler et expirés : statuts dérivés puis
     * alertes d'expiration, qui se referment seules quand la condition
     * disparaît (le lot est traité, vendu ou épuisé).
     */
    public function refresh(Bakery $bakery, CarbonInterface $date): void
    {
        $today = $date->copy()->startOfDay();

        $batches = ProductionBatch::withoutBakeryScope()
            ->where('bakery_id', $bakery->getKey())
            ->where(static function (Builder $query): void {
                $query->where('available_quantity', '>', 0)
                    ->orWhere('status', '!=', BatchStatus::Depleted->value);
            })
            ->orderBy('expires_on')
            ->orderBy('id')
            ->get();

        /** @var array<int, list<ProductionBatch>> $expiredByProduct */
        $expiredByProduct = [];
        /** @var array<int, list<ProductionBatch>> $soonByProduct */
        $soonByProduct = [];
        $productIds = [];

        foreach ($batches as $batch) {
            $status = $this->lots->deriveStatus($batch, $today);

            if ($batch->status !== $status) {
                $batch->status = $status;
                $batch->save();
            }

            $productIds[$batch->product_id] = true;

            if (! Decimal::isPositive(Decimal::trim((string) $batch->available_quantity))) {
                continue;
            }

            if (self::isExpiredOn($batch, $today)) {
                $expiredByProduct[$batch->product_id][] = $batch;

                continue;
            }

            $soonDays = (int) config('boulangerie.expiry.soon_days', 1);

            if ($batch->expires_on !== null && $batch->expires_on->lte($today->addDays($soonDays))) {
                $soonByProduct[$batch->product_id][] = $batch;
            }
        }

        // Les produits dont tous les lots sont épuisés ne sont plus dans
        // la requête : leurs alertes d'expiration doivent pourtant être
        // refermées quand la condition disparaît.
        foreach (StockAlert::withoutBakeryScope()
            ->where('bakery_id', $bakery->getKey())
            ->whereIn('type', [StockAlertType::ExpirySoon->value, StockAlertType::Expired->value])
            ->where('status', '!=', StockAlertStatus::Resolved->value)
            ->pluck('product_id') as $alertProductId) {
            $productIds[(int) $alertProductId] = true;
        }

        foreach (array_keys($productIds) as $productId) {
            $product = Product::query()->find((int) $productId);

            if ($product === null) {
                continue;
            }

            if (isset($expiredByProduct[$productId])) {
                $this->alerts->open(
                    $bakery,
                    $product,
                    StockAlertType::Expired,
                    $this->message($expiredByProduct[$productId], true),
                    Decimal::trim($this->totalAvailable($expiredByProduct[$productId])),
                );
            } else {
                $this->alerts->resolve($bakery, $product, StockAlertType::Expired);
            }

            if (! isset($expiredByProduct[$productId]) && isset($soonByProduct[$productId])) {
                $this->alerts->open(
                    $bakery,
                    $product,
                    StockAlertType::ExpirySoon,
                    $this->message($soonByProduct[$productId], false),
                    Decimal::trim($this->totalAvailable($soonByProduct[$productId])),
                );
            } else {
                $this->alerts->resolve($bakery, $product, StockAlertType::ExpirySoon);
            }
        }
    }

    /**
     * Transforme le restant des lots expirés en perte (cahier §8.2) :
     * mouvement `LossOut`, valorisation, lot conservé comme historique.
     *
     * Idempotente : un lot déjà vidé n'est plus candidat, et chaque
     * mouvement porte la clé `batch-expiry:{lot}:{date}`.
     */
    public function convertExpired(Bakery $bakery, CarbonInterface $date, ?User $user = null): ?LossRecord
    {
        $today = $date->copy()->startOfDay();

        $candidates = ProductionBatch::withoutBakeryScope()
            ->where('bakery_id', $bakery->getKey())
            ->where('available_quantity', '>', 0)
            ->whereNotNull('expires_on')
            ->orderBy('expires_on')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $lines = [];

        foreach ($candidates as $batch) {
            if (! self::isExpiredOn($batch, $today)) {
                continue;
            }

            $available = Decimal::trim((string) $batch->available_quantity);

            if (! Decimal::isPositive($available)) {
                continue;
            }

            $lines[] = [
                'product_id' => $batch->product_id,
                'quantity' => $available,
                'batch_id' => $batch->getKey(),
                'comment' => "Lot {$batch->reference} expiré",
            ];
        }

        if ($lines === []) {
            return null;
        }

        return $this->losses->record(
            bakery: $bakery,
            reason: LossReason::Expiry,
            lines: $lines,
            user: $user,
            occurredOn: $today,
            origin: LossOrigin::Expiry,
        );
    }

    /**
     * @param  list<ProductionBatch>  $batches
     */
    private function message(array $batches, bool $expired): string
    {
        $count = count($batches);
        $quantity = Decimal::trim($this->totalAvailable($batches));
        $subject = $count > 1 ? "{$count} lots" : 'Le lot '.$batches[0]->reference;

        if ($expired) {
            return sprintf('%s expiré%s : %s unités à traiter.', $subject, $count > 1 ? 's' : '', $quantity);
        }

        $until = $batches[0]->expires_on?->toDateString() ?? 'sa date limite';

        return sprintf('%s à écouler : %s unités vendables jusqu’au %s.', $subject, $quantity, $until);
    }

    /**
     * @param  list<ProductionBatch>  $batches
     */
    private function totalAvailable(array $batches): string
    {
        $total = '0';

        foreach ($batches as $batch) {
            $total = Decimal::add($total, (string) $batch->available_quantity);
        }

        return $total;
    }
}
