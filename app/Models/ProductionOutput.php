<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductionOutputFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Sortie de produits finis d'une production (cahier §7.3).
 *
 * @property int $id
 * @property int $production_order_id
 * @property int $product_id
 * @property int|null $unit_id
 * @property string $quantity
 * @property int $unit_cost
 * @property Carbon $produced_on
 */
class ProductionOutput extends Model
{
    /** @use HasFactory<ProductionOutputFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'production_order_id',
        'product_id',
        'unit_id',
        'quantity',
        'unit_cost',
        'produced_on',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'integer',
            'produced_on' => 'date',
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
     * @return HasOne<ProductionBatch, $this>
     */
    public function batch(): HasOne
    {
        return $this->hasOne(ProductionBatch::class);
    }
}
