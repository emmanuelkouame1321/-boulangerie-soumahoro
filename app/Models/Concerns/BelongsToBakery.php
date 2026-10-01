<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Bakery;
use App\Models\Scopes\BakeryScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle rattaché à un site.
 *
 * L'ajout de ce trait applique automatiquement l'isolation par site et
 * expose la relation `bakery`. Utiliser sur tout modèle portant un
 * `bakery_id` (stocks, productions, achats, ventes, charges…).
 */
trait BelongsToBakery
{
    protected static function bootBelongsToBakery(): void
    {
        static::addGlobalScope(new BakeryScope);
    }

    /**
     * @return BelongsTo<Bakery, $this>
     */
    public function bakery(): BelongsTo
    {
        return $this->belongsTo(Bakery::class);
    }

    /**
     * Accès transversal volontaire, réservé au Super-Admin et aux
     * traitements de consolidation / reporting. Jamais dans un écran
     * opérationnel.
     *
     * @return Builder<static>
     */
    public static function withoutBakeryScope(): Builder
    {
        /** @var Builder<static> $builder */
        $builder = static::withoutGlobalScope(BakeryScope::class);

        return $builder;
    }
}
