<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBakery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Préférence de notification par site et par type (cahier §22, §24).
 *
 * Les canaux sont indépendants : `internal` pilote l'écran
 * /notifications, `email` l'envoi différé en queue. Une ligne absente
 * vaut la valeur par défaut de `config('boulangerie.notifications')`.
 *
 * @property int $id
 * @property int $bakery_id
 * @property string $type
 * @property bool $internal
 * @property bool $email
 */
class NotificationPreference extends Model
{
    use BelongsToBakery;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'type',
        'internal',
        'email',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'internal' => 'boolean',
            'email' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Bakery, $this>
     */
    public function bakery(): BelongsTo
    {
        return $this->belongsTo(Bakery::class, 'bakery_id');
    }
}
