<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UnitConversionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Conversion entre deux unités (cahier §4.3).
 *
 * `factor` = quantité d'unité cible pour 1 unité source.
 * Exemple : 1 sac → 50 kg, factor = 50.
 *
 * @property int $id
 * @property int $from_unit_id
 * @property int $to_unit_id
 * @property string $factor
 */
class UnitConversion extends Model
{
    /** @use HasFactory<UnitConversionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'from_unit_id',
        'to_unit_id',
        'factor',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // decimal, jamais float : la précision d'une quantité métier
            // ne doit pas dépendre de la représentation binaire.
            'factor' => 'decimal:8',
        ];
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'from_unit_id');
    }

    /**
     * @return BelongsTo<Unit, $this>
     */
    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }
}
