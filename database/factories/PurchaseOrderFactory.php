<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\Bakery;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'supplier_id' => Supplier::factory(),
            'created_by' => null,
            'reference' => 'PO-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(1000, 9999),
            'status' => PurchaseOrderStatus::Ordered,
            'ordered_at' => now()->toDateString(),
            'expected_at' => now()->addDays(3)->toDateString(),
            'validated_at' => null,
            'total' => 0,
            'note' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => PurchaseOrderStatus::Draft,
            'ordered_at' => null,
        ]);
    }

    public function validated(): static
    {
        return $this->state(fn (): array => [
            'status' => PurchaseOrderStatus::Validated,
            'validated_at' => now(),
        ]);
    }
}
