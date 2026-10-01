<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DayClosingStatus;
use App\Models\Bakery;
use App\Models\DayClosing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DayClosing>
 */
class DayClosingFactory extends Factory
{
    protected $model = DayClosing::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'created_by' => null,
            'validated_by' => null,
            'reference' => 'CLO-'.now()->format('Ymd').'-'.fake()->unique()->numberBetween(1_000, 9_999),
            'status' => DayClosingStatus::Validated,
            'closing_date' => now()->toDateString(),
            'produced_total' => 0,
            'sold_total' => 0,
            'remaining_total' => 0,
            'lost_total' => 0,
            'returned_total' => 0,
            'gap_total' => 0,
            'note' => null,
            'validated_at' => now(),
        ];
    }
}
