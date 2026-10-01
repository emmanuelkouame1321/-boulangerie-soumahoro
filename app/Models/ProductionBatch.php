<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BatchStatus;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\ProductionBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Lot de produits finis (cahier §8).
 *
 * Chaque production crée un lot : quantité initiale, disponible, vendue,
 * perdue, date de production et date limite de vente. Le lot est conservé
 * comme historique. Le statut est dérivé des quantités et de la date
 * limite.
 *
 * @property int $id
 * @property int $bakery_id
 * @property int|null $production_order_id
 * @property int|null $production_output_id
 * @property int $product_id
 * @property string $reference
 * @property string $initial_quantity
 * @property string $available_quantity
 * @property string $sold_quantity
 * @property string $lost_quantity
 * @property Carbon $produced_on
 * @property Carbon|null $expires_on
 * @property BatchStatus $status
 * @property string|null $note
 */
class ProductionBatch extends Model
{
    /** @use HasFactory<ProductionBatchFactory> */
    use BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'production_order_id',
        'production_output_id',
        'product_id',
        'reference',
        'initial_quantity',
        'available_quantity',
        'sold_quantity',
        'lost_quantity',
        'produced_on',
        'expires_on',
        'status',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'initial_quantity' => 'decimal:4',
            'available_quantity' => 'decimal:4',
            'sold_quantity' => 'decimal:4',
            'lost_quantity' => 'decimal:4',
            'produced_on' => 'date',
            'expires_on' => 'date',
            'status' => BatchStatus::class,
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
     * @return BelongsTo<ProductionOutput, $this>
     */
    public function output(): BelongsTo
    {
        return $this->belongsTo(ProductionOutput::class, 'production_output_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Le lot est-il épuisé ?
     */
    public function isDepleted(): bool
    {
        return bccomp($this->available_quantity, '0', 4) <= 0;
    }
}
