<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\PayrollPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Versement de salaire (cahier §17).
 *
 * @property int $id
 * @property int $bakery_id
 * @property int $payroll_item_id
 * @property int $employee_id
 * @property string $method
 * @property int $amount
 * @property Carbon $paid_at
 * @property int|null $created_by
 * @property string|null $note
 */
class PayrollPayment extends Model
{
    /** @use HasFactory<PayrollPaymentFactory> */
    use AuditsChanges, BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'payroll_item_id',
        'employee_id',
        'method',
        'amount',
        'paid_at',
        'created_by',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<PayrollItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class, 'payroll_item_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function methodLabel(): string
    {
        return (string) config("boulangerie.payment_methods.{$this->method}", $this->method);
    }
}
