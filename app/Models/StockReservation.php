<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockReservationStatus;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\StockReservationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Réservation de stock (cahier §9.1 et §37.3).
 *
 * Immobilise une quantité sans la consommer : le disponible baisse, le
 * physique reste inchangé.
 *
 * @property int $id
 * @property int $bakery_id
 * @property int $product_id
 * @property string $quantity
 * @property string|null $source_type
 * @property int|null $source_id
 * @property StockReservationStatus $status
 * @property string|null $reference
 * @property Carbon|null $expires_at
 * @property-read Bakery $bakery
 * @property-read Product $product
 */
class StockReservation extends Model
{
    /** @use HasFactory<StockReservationFactory> */
    use BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'product_id',
        'quantity',
        'source_type',
        'source_id',
        'status',
        'reference',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'status' => StockReservationStatus::class,
            'expires_at' => 'datetime',
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
     * Document à l'origine de la réservation.
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<StockReservation>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', StockReservationStatus::Active->value);
    }
}
