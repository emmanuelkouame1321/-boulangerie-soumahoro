<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayrollStatus;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\PayrollPeriodFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * Période de paie (cahier §17).
 *
 * Unique par site et par mois. Tant qu'elle est en brouillon, ses lignes
 * sont modifiables ; dès qu'elle est validée, elle est figée.
 *
 * @property int $id
 * @property int $bakery_id
 * @property string $reference
 * @property int $period_year
 * @property int $period_month
 * @property Carbon $started_on
 * @property Carbon $ended_on
 * @property PayrollStatus $status
 * @property int $total_base
 * @property int $total_bonuses
 * @property int $total_advances
 * @property int $total_deductions
 * @property int $total_net
 * @property Carbon|null $validated_at
 * @property Carbon|null $paid_at
 * @property string|null $note
 */
class PayrollPeriod extends Model
{
    /** @use HasFactory<PayrollPeriodFactory> */
    use BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'reference',
        'period_year',
        'period_month',
        'started_on',
        'ended_on',
        'status',
        'total_base',
        'total_bonuses',
        'total_advances',
        'total_deductions',
        'total_net',
        'validated_by',
        'validated_at',
        'paid_by',
        'paid_at',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PayrollStatus::class,
            'period_year' => 'integer',
            'period_month' => 'integer',
            'started_on' => 'date',
            'ended_on' => 'date',
            'total_base' => 'integer',
            'total_bonuses' => 'integer',
            'total_advances' => 'integer',
            'total_deductions' => 'integer',
            'total_net' => 'integer',
            'validated_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PayrollItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class)->orderBy('id');
    }

    /**
     * @return HasManyThrough<PayrollPayment, PayrollItem, $this>
     */
    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            PayrollPayment::class,
            PayrollItem::class,
            'payroll_period_id',
            'payroll_item_id'
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function isLocked(): bool
    {
        return $this->status->isLocked();
    }

    public function monthLabel(): string
    {
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        return ($months[$this->period_month] ?? $this->period_month).' '.$this->period_year;
    }

    /**
     * @param  Builder<PayrollPeriod>  $query
     */
    public function scopeForYear(Builder $query, int $year): void
    {
        $query->where('period_year', $year);
    }
}
