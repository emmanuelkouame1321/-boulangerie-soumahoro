<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paramètre propre à un site (cahier §19.1).
 *
 * La valeur est stockée encodée en JSON afin de supporter aussi bien un
 * scalaire qu'une structure.
 *
 * @property int $id
 * @property int $bakery_id
 * @property string $key
 * @property mixed $value
 */
class BakerySetting extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'key',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * @return BelongsTo<Bakery, $this>
     */
    public function bakery(): BelongsTo
    {
        return $this->belongsTo(Bakery::class);
    }
}
