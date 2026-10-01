<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fournisseur (cahier §10).
 *
 * Catalogue partagé entre les sites, comme les produits. Un fournisseur
 * n'est jamais supprimé physiquement : il est désactivé.
 *
 * @property int $id
 * @property string $name
 * @property string|null $contact_name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $identification
 * @property string|null $categories
 * @property string|null $payment_terms
 * @property int|null $delivery_delay_days
 * @property bool $is_active
 * @property string|null $notes
 */
class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'contact_name',
        'phone',
        'email',
        'address',
        'identification',
        'categories',
        'payment_terms',
        'delivery_delay_days',
        'is_active',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivery_delay_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Produits fournis par ce fournisseur (cahier §10).
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_suppliers')
            ->withPivot(['is_primary', 'negotiated_price', 'lead_time_days', 'supplier_reference'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<PurchaseOrder, $this>
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * @param  Builder<Supplier>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Supplier>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('contact_name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('categories', 'like', "%{$term}%");
        });
    }
}
