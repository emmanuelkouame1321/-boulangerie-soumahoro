<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockAlertMode;
use Database\Factories\StockAlertRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Règle d'alerte de stock (cahier §9.2 et §9.3).
 *
 * Une règle sans site s'applique partout ; une règle propre à un site la
 * surcharge. Le mode et la période de cumul sont configurables et ne sont
 * jamais codés en dur.
 *
 * @property int $id
 * @property int|null $bakery_id
 * @property int $product_id
 * @property StockAlertMode $mode
 * @property string $threshold
 * @property string|null $cumulation_period
 * @property bool $is_active
 */
class StockAlertRule extends Model
{
    /** @use HasFactory<StockAlertRuleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'product_id',
        'mode',
        'threshold',
        'cumulation_period',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => StockAlertMode::class,
            'threshold' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Bakery, $this>
     */
    public function bakery(): BelongsTo
    {
        return $this->belongsTo(Bakery::class);
    }
}
