<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBakery;
use Database\Factories\PayrollItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ligne de paie (cahier §17).
 *
 * @property int $id
 * @property int $bakery_id
 * @property int $payroll_period_id
 * @property int $employee_id
 * @property int $base_salary
 * @property int $bonuses
 * @property int $advances
 * @property int $deductions
 * @property int $net_salary
 * @property string|null $note
 */
class PayrollItem extends Model
{
    /** @use HasFactory<PayrollItemFactory> */
    use BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'payroll_period_id',
        'employee_id',
        'base_salary',
        'bonuses',
        'advances',
        'deductions',
        'net_salary',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_salary' => 'integer',
            'bonuses' => 'integer',
            'advances' => 'integer',
            'deductions' => 'integer',
            'net_salary' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PayrollPeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return HasMany<PayrollPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class);
    }

    public function paidTotal(): int
    {
        return (int) $this->payments()->sum('amount');
    }
}
