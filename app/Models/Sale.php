<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SaleStatus;
use App\Enums\SaleType;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Vente (cahier §14 et §25).
 *
 * Une vente validée a déduit le stock de produits finis. Les montants sont
 * des entiers FCFA. `idempotency_key` rend l'enregistrement rejouable sans
 * double déduction (cahier §29).
 *
 * @property int $id
 * @property int $bakery_id
 * @property int|null $customer_id
 * @property int|null $created_by
 * @property int|null $validated_by
 * @property string $reference
 * @property SaleType $type
 * @property SaleStatus $status
 * @property Carbon $sale_date
 * @property int $subtotal
 * @property int $discount
 * @property int $total
 * @property int $paid_total
 * @property string|null $idempotency_key
 * @property string|null $note
 * @property Carbon|null $validated_at
 */
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use AuditsChanges, BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'customer_id',
        'created_by',
        'validated_by',
        'reference',
        'type',
        'status',
        'sale_date',
        'subtotal',
        'discount',
        'total',
        'paid_total',
        'idempotency_key',
        'note',
        'validated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SaleType::class,
            'status' => SaleStatus::class,
            'sale_date' => 'date',
            'subtotal' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'paid_total' => 'integer',
            'validated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
     * @return HasMany<SaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isValidated(): bool
    {
        return $this->status->isValidated();
    }

    /**
     * @param  Builder<Sale>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('reference', 'like', "%{$term}%")
                ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', "%{$term}%"));
        });
    }
}
