<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Unité de mesure (cahier §4.3).
 *
 * @property int $id
 * @property string $code
 * @property string $label
 * @property string $symbol
 * @property string $kind
 * @property int $position
 * @property bool $is_active
 */
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'label',
        'symbol',
        'kind',
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
     * @return HasMany<UnitConversion, $this>
     */
    public function conversionsFrom(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'from_unit_id');
    }

    /**
     * @return HasMany<UnitConversion, $this>
     */
    public function conversionsTo(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'to_unit_id');
    }

    /**
     * @param  Builder<Unit>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Unit>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('kind')->orderBy('position')->orderBy('label');
    }
}
