<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockTransferStatus;
use App\Models\Concerns\AuditsChanges;
use App\Support\Decimal;
use Database\Factories\StockTransferFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Transfert de stock inter-sites (cahier §19.3).
 *
 * Seul objet du domaine rattaché à **deux** sites : il n'utilise donc pas
 * le trait `BelongsToBakery` (dont le scope global ne saurait dire lequel
 * des deux est « le » site). L'isolation est explicite — `StockTransferPolicy`
 * autorise la consultation si l'utilisateur a accès à la source **ou** à la
 * destination — et le contrôleur filtre la liste sur les deux colonnes.
 *
 * Le stock n'est jamais écrit ici : l'expédition et la réception passent
 * par `StockMovementService::apply()`.
 *
 * @property int $id
 * @property string $reference
 * @property int $bakery_id
 * @property int $destination_bakery_id
 * @property int $product_id
 * @property int|null $unit_id
 * @property int|null $production_batch_id
 * @property string $quantity
 * @property StockTransferStatus $status
 * @property Carbon $transferred_on
 * @property string|null $reason
 * @property string|null $note
 * @property int|null $created_by
 * @property int|null $sent_by
 * @property Carbon|null $sent_at
 * @property int|null $received_by
 * @property Carbon|null $received_at
 */
class StockTransfer extends Model
{
    /** @use HasFactory<StockTransferFactory> */
    use AuditsChanges, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reference',
        'bakery_id',
        'destination_bakery_id',
        'product_id',
        'unit_id',
        'production_batch_id',
        'quantity',
        'status',
        'transferred_on',
        'reason',
        'note',
        'created_by',
        'sent_by',
        'sent_at',
        'received_by',
        'received_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'status' => StockTransferStatus::class,
            'transferred_on' => 'date',
            'sent_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Bakery, $this>
     */
    public function sourceBakery(): BelongsTo
    {
        return $this->belongsTo(Bakery::class, 'bakery_id');
    }

    /**
     * @return BelongsTo<Bakery, $this>
     */
    public function destinationBakery(): BelongsTo
    {
        return $this->belongsTo(Bakery::class, 'destination_bakery_id');
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
     * @return BelongsTo<ProductionBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
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
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function isDraft(): bool
    {
        return $this->status === StockTransferStatus::Draft;
    }

    public function isSent(): bool
    {
        return $this->status === StockTransferStatus::Sent;
    }

    public function isReceived(): bool
    {
        return $this->status === StockTransferStatus::Received;
    }

    /**
     * Le transfert concerne-t-il ce site, comme expéditeur ou destinataire ?
     */
    public function involves(int $bakeryId): bool
    {
        return (int) $this->bakery_id === $bakeryId
            || (int) $this->destination_bakery_id === $bakeryId;
    }

    /**
     * Quantité affichable (échelle réduite, sans zéros inutiles).
     */
    public function formattedQuantity(): string
    {
        return Decimal::trim((string) $this->quantity);
    }

    /**
     * @param  Builder<StockTransfer>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('reference', 'like', "%{$term}%")
                ->orWhereHas('product', function (Builder $product) use ($term): void {
                    $product->where('name', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%");
                });
        });
    }

    /**
     * Transferts concernant un des sites donnés (source ou destination).
     *
     * @param  Builder<StockTransfer>  $query
     * @param  list<int>  $bakeryIds
     */
    public function scopeInvolvingAny(Builder $query, array $bakeryIds): void
    {
        $query->where(function (Builder $query) use ($bakeryIds): void {
            $query->whereIn('bakery_id', $bakeryIds)
                ->orWhereIn('destination_bakery_id', $bakeryIds);
        });
    }
}
