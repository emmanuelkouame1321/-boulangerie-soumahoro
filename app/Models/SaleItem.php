<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SaleItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne de vente (cahier §14 et §24).
 *
 * @property int $id
 * @property int $sale_id
 * @property int $product_id
 * @property int|null $unit_id
 * @property string $quantity
 * @property int $unit_price
 * @property int $discount
 * @property int $line_total
 * @property int $unit_cost
 * @property int $position
 */
class SaleItem extends Model
{
    /** @use HasFactory<SaleItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sale_id',
        'product_id',
        'unit_id',
        'quantity',
        'unit_price',
        'discount',
        'line_total',
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
            'unit_price' => 'integer',
            'discount' => 'integer',
            'line_total' => 'integer',
            'unit_cost' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
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
