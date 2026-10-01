<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RebateBasis;
use App\Models\Concerns\BelongsToBakery;
use App\Support\Format;
use Database\Factories\WholesalerRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Palier de ristourne administrable (cahier §13).
 *
 * Un palier sélectionne une base (quantité, CA, famille, combinaison), une
 * plage de seuils et un taux en pourcentage. La famille est optionnelle et
 * ne vaut que pour la base « famille ».
 *
 * @property int $id
 * @property int $bakery_id
 * @property int|null $product_category_id
 * @property string $name
 * @property RebateBasis $basis
 * @property int $min_threshold
 * @property int|null $max_threshold
 * @property string $rate
 * @property bool $is_active
 * @property int $position
 */
class WholesalerRule extends Model
{
    /** @use HasFactory<WholesalerRuleFactory> */
    use BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'product_category_id',
        'name',
        'basis',
        'min_threshold',
        'max_threshold',
        'rate',
        'is_active',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'basis' => RebateBasis::class,
            'min_threshold' => 'integer',
            'max_threshold' => 'integer',
            'rate' => 'decimal:3',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * Le libellé de la plage de seuils, ex. « 50 001 – 100 000 » ou
     * « > 200 000 ».
     */
    public function thresholdLabel(): string
    {
        if ($this->max_threshold === null) {
            return '> '.Format::number($this->min_threshold);
        }

        return Format::number($this->min_threshold).' – '.Format::number($this->max_threshold);
    }

    /**
     * @param  Builder<WholesalerRule>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<WholesalerRule>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('basis')->orderBy('min_threshold')->orderBy('position')->orderBy('id');
    }
}
