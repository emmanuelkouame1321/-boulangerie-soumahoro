<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = fake()->unique()->lexify('unit-????');

        return [
            'code' => $code,
            'label' => fake()->word(),
            'symbol' => fake()->lexify('??'),
            'kind' => 'count',
            'position' => 0,
            'is_active' => true,
        ];
    }
}
