<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RebateBasis;
use App\Enums\RebateStatus;
use App\Models\Bakery;
use App\Models\Wholesaler;
use App\Models\WholesalerRebate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WholesalerRebate>
 */
class WholesalerRebateFactory extends Factory
{
    protected $model = WholesalerRebate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'wholesaler_id' => Wholesaler::factory(),
            'wholesaler_rule_id' => null,
            'period_year' => (int) now()->year,
            'basis' => RebateBasis::Quantity,
            'base_quantity' => '0',
            'base_value' => 0,
            'rate' => '0',
            'theoretical_value' => 0,
            'validated_value' => 0,
            'paid_value' => 0,
            'status' => RebateStatus::Projected,
            'settled_on' => null,
            'created_by' => null,
            'validated_by' => null,
            'validated_at' => null,
            'note' => null,
        ];
    }
}
