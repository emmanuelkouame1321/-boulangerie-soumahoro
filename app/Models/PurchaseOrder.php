<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Commande fournisseur (cahier §11).
 *
 * Une commande appartient à un site et à un fournisseur. Elle n'est
 * jamais supprimée physiquement : une annulation est un changement de
 * statut. Le total est un entier FCFA maintenu par PurchaseOrderService.
 *
 * @property int $id
 * @property int $bakery_id
 * @property int $supplier_id
 * @property int|null $created_by
 * @property string $reference
 * @property PurchaseOrderStatus $status
 * @property Carbon|null $ordered_at
 * @property Carbon|null $expected_at
 * @property Carbon|null $validated_at
 * @property int $total
 * @property string|null $note
 */
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'supplier_id',
        'created_by',
        'reference',
        'status',
        'ordered_at',
        'expected_at',
        'validated_at',
        'total',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'ordered_at' => 'date',
            'expected_at' => 'date',
            'validated_at' => 'datetime',
            'total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<PurchaseOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * @return HasMany<GoodsReceipt, $this>
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function isReceivable(): bool
    {
        return $this->status->isReceivable();
    }

    public function isFullyReceived(): bool
    {
        foreach ($this->items as $item) {
            if (! $item->isFullyReceived()) {
                return false;
            }
        }

        return $this->items->isNotEmpty();
    }

    /**
     * @param  Builder<PurchaseOrder>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('reference', 'like', "%{$term}%")
                ->orWhereHas('supplier', function (Builder $supplier) use ($term): void {
                    $supplier->where('name', 'like', "%{$term}%");
                });
        });
    }
}
