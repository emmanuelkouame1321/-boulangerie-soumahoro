<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BakeryStatus;
use Database\Factories\BakeryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Boulangerie / site (cahier §19).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $logo_path
 * @property int|null $manager_id
 * @property BakeryStatus $status
 * @property string|null $notes
 */
class Bakery extends Model
{
    /** @use HasFactory<BakeryFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
        'logo_path',
        'manager_id',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BakeryStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /**
     * @return HasMany<BakerySetting, $this>
     */
    public function settings(): HasMany
    {
        return $this->hasMany(BakerySetting::class);
    }

    /**
     * @param  Builder<Bakery>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', BakeryStatus::Active->value);
    }

    public function isActive(): bool
    {
        return $this->status === BakeryStatus::Active;
    }

    /**
     * URL publique du logo du site, ou null s'il n'en a pas (ADR-032).
     */
    public function logoUrl(): ?string
    {
        if ($this->logo_path === null) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    /**
     * Première lettre du nom, affichée à défaut de logo.
     */
    public function initial(): string
    {
        $name = trim((string) $this->name);

        return $name === '' ? 'B' : mb_strtoupper(mb_substr($name, 0, 1));
    }

    /**
     * Valeur paramétrée du site, sinon la valeur par défaut fournie.
     *
     * Permet de surcharger site par site les valeurs de
     * config/boulangerie.php sans toucher au code (cahier §53.7).
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        $setting = $this->relationLoaded('settings')
            ? $this->settings->firstWhere('key', $key)
            : $this->settings()->where('key', $key)->first();

        if ($setting === null) {
            return $default;
        }

        return $setting->value ?? $default;
    }

    /**
     * @return EloquentCollection<int, static>
     */
    public static function selectable(): EloquentCollection
    {
        return static::query()->orderBy('name')->get();
    }
}
