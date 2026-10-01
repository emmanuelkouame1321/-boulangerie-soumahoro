<?php

declare(strict_types=1);

namespace App\Domain\Sites;

use App\Enums\Role;
use App\Models\Bakery;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Contexte de boulangerie courant (cahier §3.1 et §19.2).
 *
 * Source unique de vérité du « site actif » :
 *  - les écrans y lisent le site courant ;
 *  - le scope global BakeryScope s'y adosse pour isoler les données.
 *
 * Convention :
 *  - `current() === null` signifie « vision consolidée » (tous les sites),
 *    ce qui n'est autorisé que pour le Super-Admin ;
 *  - pour tout autre utilisateur, l'absence de site courant ferme l'accès
 *    aux données plutôt que de l'ouvrir.
 *
 * Ce service est un singleton. Il est renseigné à chaque requête par
 * ResolveBakeryContext — ne jamais s'appuyer sur un état persistant venant
 * d'une requête précédente.
 */
final class BakeryContext
{
    /** Valeur de session signifiant « toutes les boulangeries ». */
    public const ALL_SITES = 0;

    public const SESSION_KEY = 'bakery_id';

    private ?User $user = null;

    private ?Bakery $current = null;

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    /**
     * Utilisateur courant.
     *
     * Le middleware renseigne explicitement ce contexte à chaque requête.
     * Le repli sur `auth()` couvre les cas où le contexte est sollicité
     * hors requête HTTP (Livewire, commandes, tests).
     */
    public function user(): ?User
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $authenticated = auth()->user();

        return $authenticated instanceof User ? $authenticated : null;
    }

    public function setCurrent(?Bakery $bakery): void
    {
        $this->current = $bakery;
    }

    public function current(): ?Bakery
    {
        return $this->current;
    }

    public function hasCurrent(): bool
    {
        return $this->current !== null;
    }

    /**
     * Le Super-Admin est le seul à pouvoir travailler hors d'un site
     * précis (vision consolidée — cahier §19.2).
     */
    public function allowsAllSites(): bool
    {
        return $this->user()?->hasRole(Role::SuperAdmin->value) === true;
    }

    /**
     * Faut-il filtrer les requêtes sur le site courant ?
     *
     * Hors requête HTTP authentifiée (CLI, queue, seeders) on ne filtre
     * pas : ces traitements doivent poser explicitement leur contexte via
     * setCurrent() s'ils ciblent un site.
     */
    public function shouldScopeQueries(): bool
    {
        if ($this->user() === null) {
            return false;
        }

        if ($this->allowsAllSites()) {
            return $this->current !== null;
        }

        return true;
    }

    /**
     * Sites que l'utilisateur courant peut ouvrir.
     *
     * Le site par défaut est placé en tête pour que `first()` désigne le
     * site à ouvrir par défaut.
     *
     * @return Collection<int, Bakery>
     */
    public function availableBakeries(): Collection
    {
        $user = $this->user();

        if ($user === null) {
            return collect();
        }

        if ($this->allowsAllSites()) {
            return Bakery::selectable();
        }

        return $user->bakeries()
            ->orderByDesc('bakery_user.is_default')
            ->orderBy('name')
            ->get();
    }

    /**
     * Change le site courant et le mémorise pour les requêtes suivantes.
     *
     * Le changement doit être explicitement confirmé par l'utilisateur
     * (Design System §12) : ne jamais appeler cette méthode depuis une
     * soumission de formulaire en cours.
     */
    public function switchTo(?Bakery $bakery): void
    {
        $this->current = $bakery;

        if (! $this->hasSession()) {
            return;
        }

        session()->put(
            self::SESSION_KEY,
            $bakery?->getKey() ?? self::ALL_SITES
        );
    }

    public function forget(): void
    {
        $this->current = null;

        if ($this->hasSession()) {
            session()->forget(self::SESSION_KEY);
        }
    }

    private function hasSession(): bool
    {
        return app()->bound('request') && request()->hasSession();
    }
}
