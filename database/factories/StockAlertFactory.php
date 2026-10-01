<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StockAlertStatus;
use App\Enums\StockAlertType;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\StockAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAlert>
 */
class StockAlertFactory extends Factory
{
    protected $model = StockAlert::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'product_id' => Product::factory(),
            'stock_alert_rule_id' => null,
            'type' => StockAlertType::Low,
            'status' => StockAlertStatus::Open,
            'threshold' => 10,
            'observed_value' => 5,
            'message' => null,
            'triggered_at' => now(),
            'acknowledged_by' => null,
            'acknowledged_at' => null,
            'resolved_at' => null,
        ];
    }
}
