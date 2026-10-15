<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StockTransferStatus;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\StockTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
{
    protected $model = StockTransfer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'TRF-'.now()->format('Ym').'-'.fake()->unique()->numberBetween(1_000, 9_999),
            'bakery_id' => Bakery::factory(),
            'destination_bakery_id' => Bakery::factory(),
            'product_id' => Product::factory(),
            'unit_id' => null,
            'production_batch_id' => null,
            'quantity' => 10,
            'status' => StockTransferStatus::Draft,
            'transferred_on' => now()->toDateString(),
            'reason' => null,
            'note' => null,
            'created_by' => null,
            'sent_by' => null,
            'sent_at' => null,
            'received_by' => null,
            'received_at' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => StockTransferStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function received(): static
    {
        return $this->state(fn (): array => [
            'status' => StockTransferStatus::Received,
            'sent_at' => now()->subHour(),
            'received_at' => now(),
        ]);
    }
}
