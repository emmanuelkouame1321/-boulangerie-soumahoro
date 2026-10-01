<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => 'SKU-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => fake()->words(2, true),
            'product_category_id' => null,
            'type' => ProductType::RawMaterial,
            'primary_unit_id' => Unit::factory(),
            'secondary_unit_id' => null,
            'purchase_price' => 0,
            'sale_price' => 0,
            'wholesale_price' => 0,
            'alert_threshold' => null,
            'alert_mode' => 'stock_threshold',
            'shelf_life_days' => null,
            'is_active' => true,
            'description' => null,
            'photo_path' => null,
        ];
    }

    public function finishedGood(): static
    {
        return $this->state(fn (): array => ['type' => ProductType::FinishedGood]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
