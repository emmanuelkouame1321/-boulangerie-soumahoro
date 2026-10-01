<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Recette / nomenclature de production (cahier §5 et §6).
 *
 * La recette est partagée entre les sites, comme le catalogue produits :
 * elle décrit les ressources nécessaires par lot et le rendement
 * théorique. Les quantités sont en décimal exact.
 *
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string|null $reference
 * @property string $yield_quantity
 * @property int|null $production_time_minutes
 * @property int|null $shelf_life_days
 * @property string|null $allowed_loss_percent
 * @property bool $is_active
 * @property string|null $note
 */
class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'name',
        'reference',
        'yield_quantity',
        'production_time_minutes',
        'shelf_life_days',
        'allowed_loss_percent',
        'is_active',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'yield_quantity' => 'decimal:4',
            'production_time_minutes' => 'integer',
            'shelf_life_days' => 'integer',
            'allowed_loss_percent' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<RecipeItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @param  Builder<Recipe>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Recipe>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('reference', 'like', "%{$term}%");
        });
    }
}
