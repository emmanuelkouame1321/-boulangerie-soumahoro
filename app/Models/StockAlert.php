<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockAlertStatus;
use App\Enums\StockAlertType;
use App\Models\Concerns\BelongsToBakery;
use Database\Factories\StockAlertFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Alerte de stock déclenchée (cahier §9.2, §9.3 et §22).
 *
 * Une alerte conserve la valeur observée et le seuil, reste acquittable,
 * et se referme automatiquement lorsque la condition disparaît.
 *
 * @property int $id
 * @property int $bakery_id
 * @property int $product_id
 * @property int|null $stock_alert_rule_id
 * @property StockAlertType $type
 * @property StockAlertStatus $status
 * @property string|null $threshold
 * @property string|null $observed_value
 * @property string|null $message
 * @property Carbon $triggered_at
 * @property int|null $acknowledged_by
 * @property Carbon|null $acknowledged_at
 * @property Carbon|null $resolved_at
 */
class StockAlert extends Model
{
    /** @use HasFactory<StockAlertFactory> */
    use BelongsToBakery, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'product_id',
        'stock_alert_rule_id',
        'type',
        'status',
        'threshold',
        'observed_value',
        'message',
        'triggered_at',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StockAlertType::class,
            'status' => StockAlertStatus::class,
            'threshold' => 'decimal:4',
            'observed_value' => 'decimal:4',
            'triggered_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
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
     * @return BelongsTo<StockAlertRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(StockAlertRule::class, 'stock_alert_rule_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * @param  Builder<StockAlert>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', '!=', StockAlertStatus::Resolved->value);
    }
}
