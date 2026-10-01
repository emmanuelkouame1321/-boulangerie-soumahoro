<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\LossItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne de perte (cahier §8.2).
 *
 * Valorisation en entiers FCFA au moment de la perte : l'historique
 * reste lisible même si le prix du produit change ensuite.
 *
 * @property int $id
 * @property int $loss_record_id
 * @property int $product_id
 * @property int|null $production_batch_id
 * @property string $quantity
 * @property int $unit_value
 * @property int $line_value
 * @property string|null $comment
 */
class LossItem extends Model
{
    /** @use HasFactory<LossItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'loss_record_id',
        'product_id',
        'production_batch_id',
        'quantity',
        'unit_value',
        'line_value',
        'comment',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_value' => 'integer',
            'line_value' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<LossRecord, $this>
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(LossRecord::class, 'loss_record_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductionBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }
}
