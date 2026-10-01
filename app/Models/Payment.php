<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBakery;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Paiement d'une vente (cahier §14 et §24).
 *
 * @property int $id
 * @property int $sale_id
 * @property int $bakery_id
 * @property int|null $customer_id
 * @property int|null $created_by
 * @property string $method
 * @property int $amount
 * @property string|null $reference
 * @property Carbon $paid_at
 * @property string|null $note
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sale_id',
        'bakery_id',
        'customer_id',
        'created_by',
        'method',
        'amount',
        'reference',
        'paid_at',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
