<?php

declare(strict_types=1);

namespace App\Domain\Customers;

use App\Enums\RebateBasis;
use App\Enums\RebateStatus;
use App\Enums\SaleStatus;
use App\Enums\SaleType;
use App\Exceptions\RebateAlreadyValidatedException;
use App\Models\Bakery;
use App\Models\Sale;
use App\Models\User;
use App\Models\Wholesaler;
use App\Models\WholesalerRebate;
use App\Models\WholesalerRule;
use App\Support\Decimal;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Calcul des ristournes annuelles des grossistes (cahier §13, §37.5).
 *
 * La règle est entièrement administrable : les paliers vivent dans
 * `wholesaler_rules` (base, famille, seuil bas, seuil haut, taux). Le
 * service ne codant aucun seuil, la formule du cahier s'applique :
 *
 *     ristourne = base éligible × taux applicable
 *
 * La base éligible est le chiffre d'affaires grossiste de l'exercice
 * (filtré par famille pour la base « famille ») ; le palier est choisi sur
 * la quantité, le CA, la famille ou la combinaison des deux.
 */
final class WholesalerRebateService
{
    /**
     * Agrégat éligible d'un grossiste pour un exercice.
     *
     * @return array{quantity: string, revenue: int}
     */
    public function aggregate(Wholesaler $wholesaler, int $year, ?int $categoryId = null): array
    {
        $empty = ['quantity' => '0', 'revenue' => 0];

        if ($wholesaler->customer_id === null) {
            return $empty;
        }

        $query = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.bakery_id', $wholesaler->bakery_id)
            ->where('sales.customer_id', $wholesaler->customer_id)
            ->where('sales.type', SaleType::Wholesale->value)
            ->where('sales.status', SaleStatus::Validated->value)
            ->whereYear('sales.sale_date', $year);

        if ($categoryId !== null) {
            $query->where('products.product_category_id', $categoryId);
        }

        $row = $query
            ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) AS quantity')
            ->selectRaw('COALESCE(SUM(sale_items.line_total), 0) AS revenue')
            ->first();

        if ($row === null) {
            return $empty;
        }

        return [
            'quantity' => Decimal::trim(Decimal::normalize((string) $row->quantity)),
            'revenue' => (int) $row->revenue,
        ];
    }

    /**
     * Palier applicable au regard de la base, des seuils et de la famille.
     *
     * @param  iterable<WholesalerRule>  $rules
     */
    public function tierFor(iterable $rules, RebateBasis $basis, string $quantity, int $revenue): ?WholesalerRule
    {
        foreach ($rules as $rule) {
            if ($this->matches($rule, $basis, $quantity, $revenue)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Calcule (ou recalcule) la ristourne théorique d'un grossiste.
     *
     * Tant que la ristourne reste « théorique », elle est mise à jour ; dès
     * qu'elle est validée ou versée, elle est figée (cahier §53-11).
     */
    public function compute(
        Bakery $bakery,
        Wholesaler $wholesaler,
        int $year,
        ?RebateBasis $basis = null,
        ?User $user = null,
    ): WholesalerRebate {
        $basis ??= RebateBasis::tryFrom((string) config('boulangerie.rebate.default_basis', 'quantity'))
            ?? RebateBasis::Quantity;

        return DB::transaction(function () use ($bakery, $wholesaler, $year, $basis, $user): WholesalerRebate {
            $existing = $this->find($wholesaler, $year);

            if ($existing !== null && $existing->isLocked()) {
                throw RebateAlreadyValidatedException::for($existing);
            }

            $rules = WholesalerRule::withoutBakeryScope()
                ->where('bakery_id', $bakery->getKey())
                ->where('basis', $basis->value)
                ->where('is_active', true)
                ->ordered()
                ->get();

            $quantity = '0';
            $revenue = 0;
            $rule = null;

            foreach ($rules as $candidate) {
                $categoryId = $basis === RebateBasis::Family ? $candidate->product_category_id : null;
                $aggregate = $this->aggregate($wholesaler, $year, $categoryId);

                if ($this->matches($candidate, $basis, $aggregate['quantity'], $aggregate['revenue'])) {
                    $rule = $candidate;
                    $quantity = $aggregate['quantity'];
                    $revenue = $aggregate['revenue'];
                    break;
                }
            }

            // Aucun palier applicable : base éligible globale, taux nul.
            if ($rule === null) {
                $aggregate = $this->aggregate($wholesaler, $year);
                $quantity = $aggregate['quantity'];
                $revenue = $aggregate['revenue'];
            }

            $rate = $rule === null ? '0' : (string) $rule->rate;
            $theoretical = Decimal::applyPercent($revenue, $rate);

            $attributes = [
                'bakery_id' => $bakery->getKey(),
                'wholesaler_id' => $wholesaler->getKey(),
                'wholesaler_rule_id' => $rule?->getKey(),
                'period_year' => $year,
                'basis' => $basis,
                'base_quantity' => $quantity,
                'base_value' => $revenue,
                'rate' => $rate,
                'theoretical_value' => $theoretical,
                'created_by' => $existing === null ? $user?->getKey() : $existing->created_by,
                'status' => RebateStatus::Projected,
            ];

            if ($existing !== null) {
                $existing->fill($attributes)->save();

                return $existing->refresh();
            }

            return WholesalerRebate::create($attributes);
        });
    }

    /**
     * Fige la ristourne théorique : elle devient « validée » et n'est plus
     * recalculable.
     */
    public function validate(
        WholesalerRebate $rebate,
        User $user,
        ?int $amount = null,
        ?string $note = null,
    ): WholesalerRebate {
        if ($rebate->isLocked()) {
            throw RebateAlreadyValidatedException::for($rebate);
        }

        return DB::transaction(function () use ($rebate, $user, $amount, $note): WholesalerRebate {
            $rebate->validated_value = $amount ?? $rebate->theoretical_value;
            $rebate->status = RebateStatus::Validated;
            $rebate->validated_by = $user->getKey();
            $rebate->validated_at = now();

            if ($note !== null) {
                $rebate->note = $note;
            }

            $rebate->save();

            return $rebate->refresh();
        });
    }

    /**
     * Enregistre le versement/crédit de la ristourne. Une ristourne doit
     * avoir été validée au préalable.
     */
    public function settle(
        WholesalerRebate $rebate,
        User $user,
        ?int $amount,
        CarbonInterface $date,
        ?string $note = null,
    ): WholesalerRebate {
        if ($rebate->status !== RebateStatus::Validated) {
            throw RebateAlreadyValidatedException::for($rebate);
        }

        return DB::transaction(function () use ($rebate, $amount, $date, $note): WholesalerRebate {
            $rebate->paid_value = $amount ?? $rebate->validated_value;
            $rebate->status = RebateStatus::Paid;
            $rebate->settled_on = Carbon::instance($date);

            if ($note !== null) {
                $rebate->note = $note;
            }

            $rebate->save();

            return $rebate->refresh();
        });
    }

    public function find(Wholesaler $wholesaler, int $year): ?WholesalerRebate
    {
        return WholesalerRebate::withoutBakeryScope()
            ->where('wholesaler_id', $wholesaler->getKey())
            ->where('period_year', $year)
            ->first();
    }

    /**
     * Nombre de commandes grossistes validées d'un grossiste pour un
     * exercice : sert au taux de régularité (cahier §12.3).
     */
    public function orderCount(Wholesaler $wholesaler, int $year): int
    {
        if ($wholesaler->customer_id === null) {
            return 0;
        }

        return (int) Sale::withoutBakeryScope()
            ->where('bakery_id', $wholesaler->bakery_id)
            ->where('customer_id', $wholesaler->customer_id)
            ->where('type', SaleType::Wholesale->value)
            ->where('status', SaleStatus::Validated->value)
            ->whereYear('sale_date', $year)
            ->count();
    }

    private function matches(WholesalerRule $rule, RebateBasis $basis, string $quantity, int $revenue): bool
    {
        $min = $rule->min_threshold;
        $max = $rule->max_threshold;

        return match ($basis) {
            RebateBasis::Quantity => Decimal::compare($quantity, (string) $min) >= 0
                && ($max === null || Decimal::compare($quantity, (string) $max) <= 0),
            RebateBasis::Revenue, RebateBasis::Family => $revenue >= $min
                && ($max === null || $revenue <= $max),
            RebateBasis::Combination => $revenue >= $min
                && ($max === null || $revenue <= $max)
                && Decimal::compare($quantity, (string) $min) >= 0
                && ($max === null || Decimal::compare($quantity, (string) $max) <= 0),
        };
    }
}
