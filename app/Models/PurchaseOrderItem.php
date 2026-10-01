<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Decimal;
use Database\Factories\PurchaseOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne d'une commande fournisseur (cahier §11.2).
 *
 * @property int $id
 * @property int $purchase_order_id
 * @property int $product_id
 * @property int|null $unit_id
 * @property string $quantity
 * @property string $received_quantity
 * @property int $unit_price
 * @property int $line_total
 */
class PurchaseOrderItem extends Model
{
    /** @use HasFactory<PurchaseOrderItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'unit_id',
        'quantity',
        'received_quantity',
        'unit_price',
        'line_total',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'received_quantity' => 'decimal:4',
            'unit_price' => 'integer',
            'line_total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
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
     * Quantité restant à recevoir, en décimal exact.
     */
    public function remainingQuantity(): string
    {
        $remaining = Decimal::subtract((string) $this->quantity, (string) $this->received_quantity);

        return Decimal::isNegative($remaining) ? '0' : $remaining;
    }

    /**
     * Écart signé entre la quantité reçue et la quantité commandée.
     */
    public function gap(): string
    {
        return Decimal::subtract((string) $this->received_quantity, (string) $this->quantity);
    }

    public function isFullyReceived(): bool
    {
        return Decimal::compare((string) $this->received_quantity, (string) $this->quantity) >= 0;
    }
}
