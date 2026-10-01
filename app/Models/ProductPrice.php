<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PriceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historique d'un prix produit (cahier §24).
 *
 * @property int $id
 * @property int $product_id
 * @property PriceType $price_type
 * @property int $amount
 * @property string|null $effective_from
 */
class ProductPrice extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'price_type',
        'amount',
        'effective_from',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_type' => PriceType::class,
            'amount' => 'integer',
            'effective_from' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
