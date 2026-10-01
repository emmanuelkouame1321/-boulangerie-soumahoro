<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PayrollStatus;
use App\Models\Bakery;
use App\Models\PayrollPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollPeriod>
 */
class PayrollPeriodFactory extends Factory
{
    protected $model = PayrollPeriod::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = CarbonImmutable::now()->startOfMonth();
        $suffix = fake()->unique()->numerify('####');

        return [
            'bakery_id' => Bakery::factory(),
            'reference' => 'PAY-'.$start->format('Ym').'-'.$suffix,
            'period_year' => (int) $start->year,
            'period_month' => (int) $start->month,
            'started_on' => $start->toDateString(),
            'ended_on' => $start->endOfMonth()->toDateString(),
            'status' => PayrollStatus::Draft,
            'total_base' => 0,
            'total_bonuses' => 0,
            'total_advances' => 0,
            'total_deductions' => 0,
            'total_net' => 0,
            'note' => null,
        ];
    }
}
