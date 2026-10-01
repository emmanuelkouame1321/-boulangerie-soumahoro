<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBakery;
use Database\Factories\WholesalerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Grossiste (cahier §12.1).
 *
 * Le grossiste est la fiche commerciale d'un client grossiste : contacts,
 * zone, grille de prix, quantité habituelle, conditions de paiement et date
 * d'adhésion. Le lien `customer_id` rattache la fiche aux ventes réelles et
 * permet le calcul de la ristourne (cahier §12.3 et §13).
 *
 * @property int $id
 * @property int $bakery_id
 * @property int|null $customer_id
 * @property string $name
 * @property string|null $contact_name
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $zone
 * @property string|null $client_type
 * @property string $price_mode
 * @property string $usual_quantity
 * @property string|null $payment_condition
 * @property bool $is_active
 * @property Carbon|null $joined_on
 * @property string|null $note
 */
class Wholesaler extends Model
{
    /** @use HasFactory<WholesalerFactory> */
    use BelongsToBakery, HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'customer_id',
        'name',
        'contact_name',
        'phone',
        'address',
        'zone',
        'client_type',
        'price_mode',
        'usual_quantity',
        'payment_condition',
        'is_active',
        'joined_on',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usual_quantity' => 'decimal:4',
            'is_active' => 'boolean',
            'joined_on' => 'date',
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
     * @return HasMany<WholesalerRebate, $this>
     */
    public function rebates(): HasMany
    {
        return $this->hasMany(WholesalerRebate::class)->orderByDesc('period_year');
    }

    public function priceModeLabel(): string
    {
        return (string) config(
            "boulangerie.wholesaler.price_modes.{$this->price_mode}",
            $this->price_mode
        );
    }

    public function paymentConditionLabel(): ?string
    {
        if ($this->payment_condition === null) {
            return null;
        }

        return (string) config(
            "boulangerie.wholesaler_conditions.{$this->payment_condition}",
            $this->payment_condition
        );
    }

    /**
     * @param  Builder<Wholesaler>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Wholesaler>  $query
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
                ->orWhere('zone', 'like', "%{$term}%");
        });
    }
}
