<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBakery;
use Database\Factories\StockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock matérialisé d'un produit sur un site (cahier §3.1 et §9.1).
 *
 * Il ne doit jamais être écrit directement : toute variation passe par
 * `App\Domain\Inventory\StockMovementService`, seul point d'écriture
 * autorisé. Le disponible se dérive en « physique − réservé » (cahier
 * §37.3).
 *
 * @property int $id
 * @property int $bakery_id
 * @property int $product_id
 * @property string $quantity
 * @property string $reserved_quantity
 */
class Stock extends Model
{
    /** @use HasFactory<StockFactory> */
    use BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'product_id',
        'quantity',
        'reserved_quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'reserved_quantity' => 'decimal:4',
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
     * Stock disponible = physique − réservé (cahier §37.3), en décimal
     * exact.
     */
    public function availableQuantity(): string
    {
        $available = bcsub((string) $this->quantity, (string) $this->reserved_quantity, 4);

        return bccomp($available, '0', 4) === -1 ? '0' : $available;
    }
}
