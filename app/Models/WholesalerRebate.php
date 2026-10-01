<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RebateBasis;
use App\Enums\RebateStatus;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\WholesalerRebateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ristourne calculée (cahier §13).
 *
 * La ligne est unique par grossiste et par exercice. Tant qu'elle est
 * « théorique », le recalcul la met à jour ; dès qu'elle est validée puis
 * versée, elle est figée (les historiques ne sont pas écrasés, §53-11).
 *
 * @property int $id
 * @property int $bakery_id
 * @property int $wholesaler_id
 * @property int|null $wholesaler_rule_id
 * @property int $period_year
 * @property RebateBasis $basis
 * @property string $base_quantity
 * @property int $base_value
 * @property string $rate
 * @property int $theoretical_value
 * @property int $validated_value
 * @property int $paid_value
 * @property RebateStatus $status
 * @property Carbon|null $settled_on
 * @property Carbon|null $validated_at
 * @property string|null $note
 */
class WholesalerRebate extends Model
{
    /** @use HasFactory<WholesalerRebateFactory> */
    use AuditsChanges, BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'wholesaler_id',
        'wholesaler_rule_id',
        'period_year',
        'basis',
        'base_quantity',
        'base_value',
        'rate',
        'theoretical_value',
        'validated_value',
        'paid_value',
        'status',
        'settled_on',
        'created_by',
        'validated_by',
        'validated_at',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'basis' => RebateBasis::class,
            'status' => RebateStatus::class,
            'base_quantity' => 'decimal:4',
            'base_value' => 'integer',
            'rate' => 'decimal:3',
            'theoretical_value' => 'integer',
            'validated_value' => 'integer',
            'paid_value' => 'integer',
            'settled_on' => 'date',
            'validated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Wholesaler, $this>
     */
    public function wholesaler(): BelongsTo
    {
        return $this->belongsTo(Wholesaler::class);
    }

    /**
     * @return BelongsTo<WholesalerRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(WholesalerRule::class, 'wholesaler_rule_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function isLocked(): bool
    {
        return $this->status->isLocked();
    }

    /**
     * Montant final retenu : versé s'il existe, sinon validé, sinon théorique.
     */
    public function effectiveValue(): int
    {
        if ($this->paid_value > 0) {
            return $this->paid_value;
        }

        if ($this->validated_value > 0) {
            return $this->validated_value;
        }

        return $this->theoretical_value;
    }

    /**
     * @param  Builder<WholesalerRebate>  $query
     */
    public function scopeForYear(Builder $query, int $year): void
    {
        $query->where('period_year', $year);
    }
}
