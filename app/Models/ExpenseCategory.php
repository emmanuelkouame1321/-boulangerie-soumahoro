<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBakery;
use Database\Factories\ExpenseCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catégorie de charge (cahier §18).
 *
 * @property int $id
 * @property int $bakery_id
 * @property string $code
 * @property string $name
 * @property int $position
 * @property bool $is_active
 */
class ExpenseCategory extends Model
{
    /** @use HasFactory<ExpenseCategoryFactory> */
    use BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'code',
        'name',
        'position',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @param  Builder<ExpenseCategory>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<ExpenseCategory>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('name');
    }
}
