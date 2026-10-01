<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LossOrigin;
use App\Enums\LossReason;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\LossRecordFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Registre de perte (cahier §8.2 et §15).
 *
 * Une perte n'est jamais supprimée : le registre conserve la référence,
 * le motif, l'origine (déclarée ou conversion automatique des lots
 * expirés), les totaux quantité/valeur et l'utilisateur.
 *
 * @property int $id
 * @property int $bakery_id
 * @property string $reference
 * @property LossReason $reason
 * @property LossOrigin $origin
 * @property Carbon $occurred_on
 * @property string|null $note
 * @property string $total_quantity
 * @property int $total_value
 * @property int|null $created_by
 */
class LossRecord extends Model
{
    /** @use HasFactory<LossRecordFactory> */
    use BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'reference',
        'reason',
        'origin',
        'occurred_on',
        'note',
        'total_quantity',
        'total_value',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => LossReason::class,
            'origin' => LossOrigin::class,
            'occurred_on' => 'date',
            'total_quantity' => 'decimal:4',
            'total_value' => 'integer',
        ];
    }

    /**
     * @return HasMany<LossItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(LossItem::class);
    }

    /**
     * @return BelongsTo<Bakery, $this>
     */
    public function bakery(): BelongsTo
    {
        return $this->belongsTo(Bakery::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Recherche par référence, motif ou nom de produit (écran Pertes).
     *
     * @param  Builder<LossRecord>  $query
     */
    public function scopeSearch($query, string $term): void
    {
        if (trim($term) === '') {
            return;
        }

        $query->where(static function ($builder) use ($term): void {
            $builder->where('reference', 'like', "%{$term}%")
                ->orWhereHas('items.product', static function ($productQuery) use ($term): void {
                    $productQuery->where('name', 'like', "%{$term}%");
                });
        });
    }
}
