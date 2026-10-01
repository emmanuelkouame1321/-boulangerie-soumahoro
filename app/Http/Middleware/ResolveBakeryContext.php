<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Sites\BakeryContext;
use App\Models\Bakery;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Détermine le site actif à chaque requête (cahier §19.2).
 *
 * Règle de résolution :
 *  1. Super-Admin sans site choisi  → vision consolidée ;
 *  2. autre rôle                    → son site par défaut, ou le premier ;
 *  3. un site de session devenu
 *     inaccessible                   → repli silencieux sur le défaut.
 *
 * Le choix est mémorisé en session pour que le contexte reste stable
 * d'un écran à l'autre.
 */
class ResolveBakeryContext
{
    public function __construct(private readonly BakeryContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->setUser($request->user());
        $this->context->setCurrent($this->resolve($request));

        return $next($request);
    }

    private function resolve(Request $request): ?Bakery
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        $available = $this->context->availableBakeries();

        if ($available->isEmpty()) {
            // Aucun site accessible : le scope global fermera l'accès aux
            // données de site (fail-closed) plutôt que de tout exposer.
            return null;
        }

        $requested = $request->session()->get(BakeryContext::SESSION_KEY);

        if ($requested === null) {
            // Le Super-Admin ouvre par défaut la vision consolidée ; les
            // autres rôles ont besoin d'un site précis pour travailler.
            if ($this->context->allowsAllSites()) {
                $request->session()->put(BakeryContext::SESSION_KEY, BakeryContext::ALL_SITES);

                return null;
            }

            return $this->applyDefault($request, $available->first());
        }

        if ((int) $requested === BakeryContext::ALL_SITES) {
            // Consolidé : réservé au Super-Admin.
            return $this->context->allowsAllSites()
                ? null
                : $this->applyDefault($request, $available->first());
        }

        $bakery = $available->firstWhere('id', (int) $requested);

        return $bakery ?? $this->applyDefault($request, $available->first());
    }

    /**
     * Mémorise le site retenu et le retourne.
     *
     * `null` en entrée correspond au regroupement « toutes les
     * boulangeries » (Super-Admin).
     */
    private function applyDefault(Request $request, ?Bakery $bakery): ?Bakery
    {
        if ($bakery === null) {
            return null;
        }

        $request->session()->put(BakeryContext::SESSION_KEY, $bakery->getKey());

        return $bakery;
    }
}
