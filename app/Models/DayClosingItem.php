<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DayClosingItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne de clôture journalière (cahier §15).
 *
 * @property int $id
 * @property int $day_closing_id
 * @property int $product_id
 * @property string $produced_quantity
 * @property string $sold_quantity
 * @property string $remaining_quantity
 * @property string $lost_quantity
 * @property string $returned_quantity
 * @property string $gap
 */
class DayClosingItem extends Model
{
    /** @use HasFactory<DayClosingItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'day_closing_id',
        'product_id',
        'produced_quantity',
        'sold_quantity',
        'remaining_quantity',
        'lost_quantity',
        'returned_quantity',
        'gap',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'produced_quantity' => 'decimal:4',
            'sold_quantity' => 'decimal:4',
            'remaining_quantity' => 'decimal:4',
            'lost_quantity' => 'decimal:4',
            'returned_quantity' => 'decimal:4',
            'gap' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<DayClosing, $this>
     */
    public function closing(): BelongsTo
    {
        return $this->belongsTo(DayClosing::class, 'day_closing_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
