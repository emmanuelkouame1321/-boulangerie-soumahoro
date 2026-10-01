<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecipeDeductionMode;
use App\Support\Decimal;
use Database\Factories\ProductionOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Consommation d'un ordre de production (cahier §7.3).
 *
 * @property int $id
 * @property int $production_order_id
 * @property int $product_id
 * @property int|null $unit_id
 * @property RecipeDeductionMode $deduction_mode
 * @property string $planned_quantity
 * @property string|null $actual_quantity
 * @property int $unit_cost
 * @property int $line_cost
 * @property int $position
 */
class ProductionOrderItem extends Model
{
    /** @use HasFactory<ProductionOrderItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'production_order_id',
        'product_id',
        'unit_id',
        'deduction_mode',
        'planned_quantity',
        'actual_quantity',
        'unit_cost',
        'line_cost',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'deduction_mode' => RecipeDeductionMode::class,
            'planned_quantity' => 'decimal:4',
            'actual_quantity' => 'decimal:4',
            'unit_cost' => 'integer',
            'line_cost' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProductionOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
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

    /**
     * Quantité effectivement consommée : réelle si saisie, sinon planifiée.
     */
    public function consumedQuantity(): string
    {
        return Decimal::trim((string) ($this->actual_quantity ?? $this->planned_quantity));
    }
}
