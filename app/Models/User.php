<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * Le secret TOTP et les codes de secours (hachés) sont chiffrés au
     * repos : la base ne les contient jamais en clair (cahier §31).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Sites auxquels l'utilisateur est affecté (cahier §20.4).
     *
     * Un utilisateur peut être autorisé sur plusieurs sites ; `is_default`
     * désigne le site ouvert par défaut.
     *
     * @return BelongsToMany<Bakery, $this>
     */
    public function bakeries(): BelongsToMany
    {
        return $this->belongsToMany(Bakery::class)
            ->withPivot('is_default')
            ->withTimestamps();
    }

    /**
     * L'utilisateur est-il affecté à ce site ?
     */
    public function canAccessBakery(Bakery $bakery): bool
    {
        return $this->bakeries()
            ->whereKey($bakery->getKey())
            ->exists();
    }

    /**
     * Sites dont l'utilisateur est responsable.
     *
     * @return Collection<int, Bakery>
     */
    public function managedBakeries()
    {
        return Bakery::query()->where('manager_id', $this->getKey())->get();
    }
}
