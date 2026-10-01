<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductionOrderStatus;
use App\Models\Concerns\AuditsChanges;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\ProductionOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Ordre de production (cahier §7).
 *
 * Un ordre est rattaché à un site et à une recette. Il conserve le
 * rendement (théorique et réel), l'écart et son motif, ainsi que les
 * coûts. Aucun ordre n'est supprimé : une correction passe par un
 * mouvement compensatoire ou une annulation.
 *
 * @property int $id
 * @property int $bakery_id
 * @property int|null $recipe_id
 * @property int|null $created_by
 * @property int|null $validated_by
 * @property string $reference
 * @property ProductionOrderStatus $status
 * @property string $batch_count
 * @property string $theoretical_quantity
 * @property string|null $actual_quantity
 * @property string|null $gap
 * @property string|null $gap_reason
 * @property int $theoretical_cost
 * @property int|null $actual_cost
 * @property Carbon $produced_on
 * @property Carbon|null $validated_at
 * @property string|null $note
 */
class ProductionOrder extends Model
{
    /** @use HasFactory<ProductionOrderFactory> */
    use AuditsChanges, BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'recipe_id',
        'created_by',
        'validated_by',
        'reference',
        'status',
        'batch_count',
        'theoretical_quantity',
        'actual_quantity',
        'gap',
        'gap_reason',
        'theoretical_cost',
        'actual_cost',
        'produced_on',
        'validated_at',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProductionOrderStatus::class,
            'batch_count' => 'decimal:4',
            'theoretical_quantity' => 'decimal:4',
            'actual_quantity' => 'decimal:4',
            'gap' => 'decimal:4',
            'theoretical_cost' => 'integer',
            'actual_cost' => 'integer',
            'produced_on' => 'date',
            'validated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
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
     * @return HasMany<ProductionOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<ProductionOutput, $this>
     */
    public function outputs(): HasMany
    {
        return $this->hasMany(ProductionOutput::class);
    }

    /**
     * @return HasMany<ProductionBatch, $this>
     */
    public function batches(): HasMany
    {
        return $this->hasMany(ProductionBatch::class);
    }

    public function isValidated(): bool
    {
        return $this->status === ProductionOrderStatus::Validated;
    }

    /**
     * L'écart est-il renseigné et non nul ?
     */
    public function hasGap(): bool
    {
        return $this->gap !== null && bccomp($this->gap, '0', 4) !== 0;
    }

    /**
     * @param  Builder<ProductionOrder>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('reference', 'like', "%{$term}%")
                ->orWhereHas('recipe', fn (Builder $recipe) => $recipe->where('name', 'like', "%{$term}%"));
        });
    }
}
