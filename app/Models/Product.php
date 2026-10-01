<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PriceType;
use App\Enums\ProductType;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Produit ou matière première (cahier §4).
 *
 * Le catalogue est partagé entre les sites ; seul le stock porte un
 * `bakery_id`. Les prix sont des entiers FCFA.
 *
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property int|null $product_category_id
 * @property ProductType $type
 * @property int $primary_unit_id
 * @property int|null $secondary_unit_id
 * @property int $purchase_price
 * @property int $sale_price
 * @property int $wholesale_price
 * @property string|null $alert_threshold
 * @property string $alert_mode
 * @property int|null $shelf_life_days
 * @property bool $is_active
 * @property string|null $description
 * @property string|null $photo_path
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sku',
        'name',
        'product_category_id',
        'type',
        'primary_unit_id',
        'secondary_unit_id',
        'purchase_price',
        'sale_price',
        'wholesale_price',
        'alert_threshold',
        'alert_mode',
        'shelf_life_days',
        'is_active',
        'description',
        'photo_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'purchase_price' => 'integer',
            'sale_price' => 'integer',
            'wholesale_price' => 'integer',
            'alert_threshold' => 'decimal:4',
            'shelf_life_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function primaryUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'primary_unit_id');
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function secondaryUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'secondary_unit_id');
    }

    /**
     * @return HasMany<ProductPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function isSellable(): bool
    {
        return $this->type->isSellable();
    }

    public function isStockable(): bool
    {
        return $this->type->isStockable();
    }

    /**
     * Prix applicable selon la nature d'un prix.
     */
    public function priceFor(PriceType $type): int
    {
        return match ($type) {
            PriceType::Purchase => $this->purchase_price,
            PriceType::Sale => $this->sale_price,
            PriceType::Wholesale => $this->wholesale_price,
        };
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeOfType(Builder $query, ProductType $type): void
    {
        $query->where('type', $type->value);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%");
        });
    }
}
