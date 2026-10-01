<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bakery;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollPayment>
 */
class PayrollPaymentFactory extends Factory
{
    protected $model = PayrollPayment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bakery_id' => Bakery::factory(),
            'payroll_item_id' => PayrollItem::factory(),
            'employee_id' => Employee::factory(),
            'method' => 'cash',
            'amount' => 150_000,
            'paid_at' => now()->toDateString(),
            'created_by' => null,
            'note' => null,
        ];
    }
}
