<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockMovementType;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Mouvement de stock — grand livre (cahier §3.2 et §3.3).
 *
 * Un mouvement n'est jamais supprimé ni modifié : une erreur se corrige
 * par un mouvement compensatoire. La quantité est toujours positive ; le
 * sens est porté par `type` (cf. StockMovementType::sign()).
 *
 * @property int $id
 * @property int $bakery_id
 * @property int $product_id
 * @property int|null $user_id
 * @property int|null $unit_id
 * @property StockMovementType $type
 * @property string $quantity
 * @property string|null $quantity_after
 * @property string|null $source_type
 * @property int|null $source_id
 * @property string|null $reference
 * @property string|null $reason
 * @property string|null $comment
 * @property string|null $idempotency_key
 * @property Carbon $occurred_at
 */
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'product_id',
        'user_id',
        'unit_id',
        'type',
        'quantity',
        'quantity_after',
        'source_type',
        'source_id',
        'reference',
        'reason',
        'comment',
        'idempotency_key',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'decimal:4',
            'quantity_after' => 'decimal:4',
            'occurred_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Document source du mouvement (réception, vente, perte…).
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Quantité signée telle qu'elle s'inscrit dans le grand livre.
     */
    public function signedQuantity(): string
    {
        return bcmul((string) $this->quantity, (string) $this->type->sign(), 4);
    }
}
