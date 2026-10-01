<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RebateBasis;
use App\Models\Bakery;
use App\Models\WholesalerRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WholesalerRule>
 */
class WholesalerRuleFactory extends Factory
{
    protected $model = WholesalerRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'product_category_id' => null,
            'name' => 'Palier '.fake()->numberBetween(1, 50000),
            'basis' => RebateBasis::Quantity,
            'min_threshold' => 0,
            'max_threshold' => 50000,
            'rate' => '0',
            'is_active' => true,
            'position' => 0,
        ];
    }
}
