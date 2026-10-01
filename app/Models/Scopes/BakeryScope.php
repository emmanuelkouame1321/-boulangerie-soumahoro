<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Domain\Sites\BakeryContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Isolation par site (cahier §3.1, §19.2 et §53.8).
 *
 * Appliqué par défaut à tout modèle utilisant le trait BelongsToBakery :
 * un responsable ne voit que les données des sites auxquels il est
 * affecté. Le Super-Admin, lui, conserve la vision consolidée.
 *
 * En cas d'incertitude — un utilisateur non Super-Admin sans site courant —
 * le scope ferme l'accès plutôt que de l'ouvrir (fail-closed).
 */
class BakeryScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(BakeryContext::class);

        if (! $context->shouldScopeQueries()) {
            return;
        }

        $bakery = $context->current();

        if ($bakery === null) {
            // Fail-closed : aucune donnée de site ne doit fuiter.
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('bakery_id'), $bakery->getKey());
    }
}
