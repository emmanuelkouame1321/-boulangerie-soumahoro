<?php

declare(strict_types=1);

namespace App\Domain\Inventory;

use App\Domain\Notifications\AlertNotifier;
use App\Enums\StockAlertMode;
use App\Enums\StockAlertStatus;
use App\Enums\StockAlertType;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\StockAlert;
use App\Models\StockAlertRule;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Builder;

/**
 * Recalcul des alertes de stock (cahier §9.2 et §9.3).
 *
 * Appelé après chaque mouvement de stock. Évalue les deux modes :
 *  - seuil de stock        : stock actuel <= seuil ;
 *  - seuil cumulé          : consommation cumulée >= seuil, sur une
 *                            période configurable.
 *
 * Une alerte déjà ouverte est mise à jour (jamais dupliquée) ; celle dont
 * la condition disparaît est automatiquement résolue. Les règles
 * propres au site priment sur les règles globales.
 */
final class StockAlertService
{
    public function __construct(
        private readonly StockService $stocks,
        private readonly AlertNotifier $notifier,
    ) {}

    public function recalculate(Product $product, Bakery $bakery): void
    {
        $desired = $this->desiredAlerts($product, $bakery);

        $existing = StockAlert::withoutBakeryScope()
            ->where('bakery_id', $bakery->getKey())
            ->where('product_id', $product->getKey())
            ->where('status', '!=', StockAlertStatus::Resolved->value)
            ->get();

        foreach ($desired as $typeKey => $alert) {
            $open = $existing->first(
                static fn (StockAlert $candidate): bool => $candidate->type->value === $typeKey
            );

            if ($open === null) {
                $created = StockAlert::create([
                    'bakery_id' => $bakery->getKey(),
                    'product_id' => $product->getKey(),
                    'stock_alert_rule_id' => $alert['rule']?->getKey(),
                    'type' => $typeKey,
                    'status' => StockAlertStatus::Open->value,
                    'threshold' => $alert['threshold'],
                    'observed_value' => $alert['observed'],
                    'message' => $alert['message'],
                    'triggered_at' => now(),
                ]);

                $this->notifier->alertOpened($created);

                continue;
            }

            $open->update([
                'stock_alert_rule_id' => $alert['rule']?->getKey(),
                'threshold' => $alert['threshold'],
                'observed_value' => $alert['observed'],
                'message' => $alert['message'],
            ]);
        }

        foreach ($existing as $alert) {
            if (isset($desired[$alert->type->value])) {
                continue;
            }

            // Seules les alertes gérées par ce recalcul vivent avec lui :
            // l'expiration est gérée par ExpiryService, et les alertes
            // événementielles (perte importante, écart de rendement)
            // restent ouvertes jusqu'à leur acquittement.
            if (in_array($alert->type, [StockAlertType::Low, StockAlertType::Critical, StockAlertType::Cumulated], true) === false) {
                continue;
            }

            $alert->update([
                'status' => StockAlertStatus::Resolved->value,
                'resolved_at' => now(),
            ]);
        }
    }

    /**
     * Ouvre (ou met à jour) une alerte active de type donné. Utilisé par
     * les modules qui déclenchent leurs propres alertes (péremption,
     * pertes, production) sans dupliquer le cycle de vie.
     */
    public function open(
        Bakery $bakery,
        Product $product,
        StockAlertType $type,
        string $message,
        ?string $observed = null,
        ?string $threshold = null,
    ): StockAlert {
        $open = StockAlert::withoutBakeryScope()
            ->where('bakery_id', $bakery->getKey())
            ->where('product_id', $product->getKey())
            ->where('type', $type->value)
            ->where('status', '!=', StockAlertStatus::Resolved->value)
            ->first();

        if ($open !== null) {
            $open->update([
                'threshold' => $threshold,
                'observed_value' => $observed,
                'message' => $message,
            ]);

            return $open;
        }

        $alert = StockAlert::create([
            'bakery_id' => $bakery->getKey(),
            'product_id' => $product->getKey(),
            'type' => $type->value,
            'status' => StockAlertStatus::Open->value,
            'threshold' => $threshold,
            'observed_value' => $observed,
            'message' => $message,
            'triggered_at' => now(),
        ]);

        $this->notifier->alertOpened($alert);

        return $alert;
    }

    /**
     * Referme l'alerte active de type donné, si elle existe (condition
     * disparue ou événement traité).
     */
    public function resolve(Bakery $bakery, Product $product, StockAlertType $type): void
    {
        StockAlert::withoutBakeryScope()
            ->where('bakery_id', $bakery->getKey())
            ->where('product_id', $product->getKey())
            ->where('type', $type->value)
            ->where('status', '!=', StockAlertStatus::Resolved->value)
            ->each(static function (StockAlert $alert): void {
                $alert->update([
                    'status' => StockAlertStatus::Resolved->value,
                    'resolved_at' => now(),
                ]);
            });
    }

    /**
     * Alertes qui devraient être ouvertes pour ce produit sur ce site.
     *
     * @return array<string, array{type: StockAlertType, rule: StockAlertRule|null, threshold: string|null, observed: string|null, message: string}>
     */
    private function desiredAlerts(Product $product, Bakery $bakery): array
    {
        $rules = $this->resolveRules($product, $bakery);

        $alerts = [];

        foreach ($rules as $rule) {
            if ($rule->mode === StockAlertMode::CumulatedThreshold) {
                $alert = $this->evaluateCumulated($product, $bakery, $rule);

                if ($alert !== null) {
                    $alerts[StockAlertType::Cumulated->value] = $alert;
                }

                continue;
            }

            $alert = $this->evaluateStock($product, $bakery, $rule);

            if ($alert !== null) {
                $alerts[$alert['type']->value] = $alert;
            }
        }

        return $alerts;
    }

    /**
     * Règles applicables, une par mode, la règle du site primant sur la
     * règle globale. À défaut de règle explicite, le seuil porté par le
     * produit (Phase 1.4) sert de repli.
     *
     * @return list<StockAlertRule>
     */
    private function resolveRules(Product $product, Bakery $bakery): array
    {
        $rules = StockAlertRule::query()
            ->where('product_id', $product->getKey())
            ->where('is_active', true)
            ->where(function (Builder $query) use ($bakery): void {
                $query->whereNull('bakery_id')
                    ->orWhere('bakery_id', $bakery->getKey());
            })
            ->get();

        /** @var array<string, StockAlertRule> $byMode */
        $byMode = [];

        foreach ($rules as $rule) {
            $mode = $rule->mode->value;

            if (! isset($byMode[$mode]) || ($byMode[$mode]->bakery_id === null && $rule->bakery_id !== null)) {
                $byMode[$mode] = $rule;
            }
        }

        if (! isset($byMode[StockAlertMode::StockThreshold->value])
            && $product->alert_threshold !== null
            && $product->alert_mode === StockAlertMode::StockThreshold->value) {
            $byMode[StockAlertMode::StockThreshold->value] = new StockAlertRule([
                'bakery_id' => null,
                'product_id' => $product->getKey(),
                'mode' => StockAlertMode::StockThreshold,
                'threshold' => $product->alert_threshold,
            ]);
        }

        return array_values($byMode);
    }

    /**
     * @return array{type: StockAlertType, rule: StockAlertRule|null, threshold: string, observed: string, message: string}|null
     */
    private function evaluateStock(Product $product, Bakery $bakery, StockAlertRule $rule): ?array
    {
        $observed = $this->stocks->physical($product, $bakery);
        $threshold = Decimal::trim((string) $rule->threshold);

        if (Decimal::compare($observed, $threshold) > 0) {
            return null;
        }

        $type = Decimal::compare($observed, '0') <= 0 ? StockAlertType::Critical : StockAlertType::Low;

        $message = $type === StockAlertType::Critical
            ? sprintf('Rupture de stock de « %s » (seuil %s %s).', $product->name, $threshold, $this->unit($product))
            : sprintf('Stock faible de « %s » : %s %s (seuil %s %s).', $product->name, $observed, $this->unit($product), $threshold, $this->unit($product));

        return [
            'type' => $type,
            'rule' => $rule->exists ? $rule : null,
            'threshold' => $threshold,
            'observed' => $observed,
            'message' => $message,
        ];
    }

    /**
     * @return array{type: StockAlertType, rule: StockAlertRule|null, threshold: string, observed: string, message: string}|null
     */
    private function evaluateCumulated(Product $product, Bakery $bakery, StockAlertRule $rule): ?array
    {
        $observed = $this->stocks->cumulatedConsumption($product, $bakery, $rule->cumulation_period);
        $threshold = Decimal::trim((string) $rule->threshold);

        if (Decimal::compare($observed, $threshold) < 0) {
            return null;
        }

        $message = sprintf(
            'Consommation cumulée de « %s » atteinte : %s %s (seuil %s %s).',
            $product->name,
            $observed,
            $this->unit($product),
            $threshold,
            $this->unit($product)
        );

        return [
            'type' => StockAlertType::Cumulated,
            'rule' => $rule->exists ? $rule : null,
            'threshold' => $threshold,
            'observed' => $observed,
            'message' => $message,
        ];
    }

    private function unit(Product $product): string
    {
        return (string) ($product->primaryUnit->symbol ?? '');
    }
}
