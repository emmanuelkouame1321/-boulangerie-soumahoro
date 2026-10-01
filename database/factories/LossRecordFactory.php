<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LossOrigin;
use App\Enums\LossReason;
use App\Models\Bakery;
use App\Models\LossRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LossRecord>
 */
class LossRecordFactory extends Factory
{
    protected $model = LossRecord::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'reference' => 'LOSS-'.now()->format('Ym').'-'.fake()->unique()->numberBetween(1, 9_999),
            'reason' => LossReason::Damage,
            'origin' => LossOrigin::Manual,
            'occurred_on' => now()->toDateString(),
            'note' => null,
            'total_quantity' => 0,
            'total_value' => 0,
            'created_by' => null,
        ];
    }

    public function expiry(): static
    {
        return $this->state(fn (): array => [
            'reason' => LossReason::Expiry,
            'origin' => LossOrigin::Expiry,
        ]);
    }
}
