<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StockAlertMode;
use App\Models\Product;
use App\Models\StockAlertRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAlertRule>
 */
class StockAlertRuleFactory extends Factory
{
    protected $model = StockAlertRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => null,
            'product_id' => Product::factory(),
            'mode' => StockAlertMode::StockThreshold,
            'threshold' => 10,
            'cumulation_period' => null,
            'is_active' => true,
        ];
    }
}
