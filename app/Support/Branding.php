<?php

declare(strict_types=1);

namespace App\Support;

use App\Domain\Sites\BakeryContext;
use App\Models\Bakery;

/**
 * Identité visuelle du site courant (cahier §19.1, ADR-032).
 *
 * Le nom du site vient de la table `bakeries` ; son logo sert de marque
 * dans l'application et d'icône à l'onglet du navigateur (favicon).
 *
 * Résolution du logo, dans l'ordre :
 *  1. le site actif ;
 *  2. à défaut (vision consolidée du Super-Admin, site sans logo), le
 *     premier site accessible qui en définit un, dans l'ordre de
 *     BakeryContext::availableBakeries() (site par défaut puis nom) ;
 *  3. le favicon livré avec l'application.
 *
 * Les données affichées par cet utilitaire sont de la présentation : il
 * ne modifie rien et ne décide d'aucune règle métier.
 */
final class Branding
{
    /**
     * URL du logo à afficher, ou null si aucun site n'en définit.
     */
    public static function logoUrl(): ?string
    {
        $context = app(BakeryContext::class);

        $current = $context->current();

        if ($current?->logoUrl() !== null) {
            return $current->logoUrl();
        }

        foreach ($context->availableBakeries() as $bakery) {
            if ($bakery instanceof Bakery && $bakery->logoUrl() !== null) {
                return $bakery->logoUrl();
            }
        }

        return null;
    }

    /**
     * Icône de l'onglet : le logo du site, sinon le favicon livré.
     */
    public static function faviconUrl(): string
    {
        return self::logoUrl() ?? asset('favicon.ico');
    }
}
