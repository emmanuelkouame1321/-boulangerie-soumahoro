<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecipeDeductionMode;
use Database\Factories\RecipeItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ressource d'une recette (cahier §5.2 et §5.3).
 *
 * @property int $id
 * @property int $recipe_id
 * @property int $product_id
 * @property int|null $unit_id
 * @property string $quantity
 * @property RecipeDeductionMode $deduction_mode
 * @property int|null $unit_cost
 * @property int $position
 */
class RecipeItem extends Model
{
    /** @use HasFactory<RecipeItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'recipe_id',
        'product_id',
        'unit_id',
        'quantity',
        'deduction_mode',
        'unit_cost',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'deduction_mode' => RecipeDeductionMode::class,
            'unit_cost' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
