<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GoodsReceiptStatus;
use App\Models\Bakery;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceipt>
 */
class GoodsReceiptFactory extends Factory
{
    protected $model = GoodsReceipt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'purchase_order_id' => PurchaseOrder::factory(),
            'created_by' => null,
            'validated_by' => null,
            'reference' => 'GR-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(1000, 9999),
            'status' => GoodsReceiptStatus::Draft,
            'received_at' => now(),
            'validated_at' => null,
            'note' => null,
        ];
    }

    public function validated(): static
    {
        return $this->state(fn (): array => [
            'status' => GoodsReceiptStatus::Validated,
            'validated_at' => now(),
        ]);
    }
}
