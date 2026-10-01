<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DayClosingStatus;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\DayClosingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Clôture journalière (cahier §15).
 *
 * @property int $id
 * @property int $bakery_id
 * @property int|null $created_by
 * @property int|null $validated_by
 * @property string $reference
 * @property DayClosingStatus $status
 * @property Carbon $closing_date
 * @property string $produced_total
 * @property string $sold_total
 * @property string $remaining_total
 * @property string $lost_total
 * @property string $returned_total
 * @property string $gap_total
 * @property string|null $note
 * @property Carbon|null $validated_at
 */
class DayClosing extends Model
{
    /** @use HasFactory<DayClosingFactory> */
    use BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'created_by',
        'validated_by',
        'reference',
        'status',
        'closing_date',
        'produced_total',
        'sold_total',
        'remaining_total',
        'lost_total',
        'returned_total',
        'gap_total',
        'note',
        'validated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DayClosingStatus::class,
            'closing_date' => 'date',
            'produced_total' => 'decimal:4',
            'sold_total' => 'decimal:4',
            'remaining_total' => 'decimal:4',
            'lost_total' => 'decimal:4',
            'returned_total' => 'decimal:4',
            'gap_total' => 'decimal:4',
            'validated_at' => 'datetime',
        ];
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
     * @return HasMany<DayClosingItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DayClosingItem::class);
    }

    public function hasGap(): bool
    {
        return bccomp($this->gap_total, '0', 4) !== 0;
    }
}
