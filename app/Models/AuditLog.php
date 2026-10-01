<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToBakery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Entrée du journal d'audit (cahier §32).
 *
 * Journal d'insertion seule : ni mise à jour ni suppression.
 * `user_name` fige le nom à l'instant T, l'objet survit à la suppression
 * de son enregistrement, et le site est conservé même si le site disparaît.
 *
 * @property int $id
 * @property int|null $bakery_id
 * @property int|null $user_id
 * @property string|null $user_name
 * @property string $action
 * @property string|null $auditable_type
 * @property int|null $auditable_id
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property string|null $ip_address
 * @property Carbon $created_at
 */
class AuditLog extends Model
{
    use BelongsToBakery;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bakery_id',
        'user_id',
        'user_name',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Bakery, $this>
     */
    public function bakery(): BelongsTo
    {
        return $this->belongsTo(Bakery::class, 'bakery_id');
    }

    /**
     * Objet audité, résolu lorsque la classe existe encore.
     */
    public function auditable(): ?Model
    {
        if ($this->auditable_type === null || $this->auditable_id === null) {
            return null;
        }

        if (! class_exists($this->auditable_type)) {
            return null;
        }

        return $this->auditable_type::query()->find($this->auditable_id);
    }

    /**
     * Objet audité, avec sa classe pour affichage même si elle disparaît.
     */
    public function subjectLabel(): string
    {
        if ($this->auditable_type === null) {
            return '—';
        }

        $class = substr((string) $this->auditable_type, (int) strrpos((string) $this->auditable_type, '\\') + 1);

        return $class.(($this->auditable_id ?? 0) > 0 ? ' #'.$this->auditable_id : '');
    }

    /**
     * @param  Builder<AuditLog>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('user_name', 'like', "%{$term}%")
                ->orWhere('action', 'like', "%{$term}%")
                ->orWhere('auditable_type', 'like', "%{$term}%");
        });
    }

    /**
     * @param  Builder<AuditLog>  $query
     */
    public function scopeBetween(Builder $query, string $from, string $to): void
    {
        $query->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);
    }
}
