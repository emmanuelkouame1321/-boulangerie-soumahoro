<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GoodsReceiptStatus;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\GoodsReceiptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Réception fournisseur (cahier §11.3).
 *
 * Un bon de réception enregistre les quantités réellement livrées. Sa
 * validation crée les mouvements d'entrée de stock, une seule fois.
 *
 * @property int $id
 * @property int $bakery_id
 * @property int $purchase_order_id
 * @property int|null $created_by
 * @property int|null $validated_by
 * @property string $reference
 * @property GoodsReceiptStatus $status
 * @property Carbon $received_at
 * @property Carbon|null $validated_at
 * @property string|null $note
 */
class GoodsReceipt extends Model
{
    /** @use HasFactory<GoodsReceiptFactory> */
    use AuditsChanges, BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'purchase_order_id',
        'created_by',
        'validated_by',
        'reference',
        'status',
        'received_at',
        'validated_at',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GoodsReceiptStatus::class,
            'received_at' => 'datetime',
            'validated_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * @return HasMany<GoodsReceiptItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function isValidated(): bool
    {
        return $this->status->isValidated();
    }
}
