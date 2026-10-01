<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBakery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Export généré en arrière-plan (cahier §48, §53-13).
 *
 * Un export est demandé par un utilisateur, produit un fichier par un job
 * en queue, reste téléchargeable jusqu'à `expires_at` (7 jours) puis est
 * purgé par `boulangerie:exports-prune`.
 *
 * @property int $id
 * @property int|null $bakery_id
 * @property int $user_id
 * @property string $kind
 * @property string $format
 * @property string $label
 * @property array<string, mixed>|null $filters
 * @property string $status
 * @property string|null $path
 * @property string|null $error
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Export extends Model
{
    use BelongsToBakery;

    public const STATUS_PENDING = 'pending';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'user_id',
        'kind',
        'format',
        'label',
        'filters',
        'status',
        'path',
        'error',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Bakery, $this>
     */
    public function bakery(): BelongsTo
    {
        return $this->belongsTo(Bakery::class, 'bakery_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY && $this->path !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
