<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * Construction du menu applicatif (Design System §10, §52).
 *
 * Le menu est filtré côté serveur selon les permissions : un utilisateur
 * ne doit jamais voir une entrée qu'il ne peut pas utiliser, et la route
 * elle-même reste protégée par une Policy / un middleware.
 *
 * La partie coûteuse — vérifier que la route de chaque entrée existe
 * encore, sur toute la table des routes — est résolue une fois puis
 * mémorisée (cahier §48). Le filtre par permission et l'état « actif »
 * restent calculés à chaque requête, car ils dépendent de l'utilisateur
 * et de l'URL courante.
 */
final class Navigation
{
    /**
     * Durée de vie de l'arbre résolu (24 h). La clé changeant avec la
     * configuration et la table des routes, ce délai n'est qu'un
     * nettoyage des clés devenues inutiles.
     */
    public const TTL = 86_400;

    /**
     * @return list<array{label: string|null, items: list<array<string, mixed>>}>
     */
    public static function forUser(?Authenticatable $user): array
    {
        $filtered = [];

        foreach (self::tree() as $group) {
            $items = array_values(array_filter(
                $group['items'],
                static fn (array $item): bool => self::isItemAllowed($item, $user)
            ));

            if ($items === []) {
                continue;
            }

            $filtered[] = [
                'label' => $group['label'],
                'items' => array_map(
                    static fn (array $item): array => $item + ['href' => self::href($item), 'active' => self::isActive($item)],
                    $items
                ),
            ];
        }

        return $filtered;
    }

    /**
     * Arbre du menu débarrassé des liens morts, tel que mémorisé en cache.
     *
     * @return list<array{label: string|null, items: list<array<string, mixed>>}>
     */
    public static function tree(): array
    {
        /** @var list<array{label: string|null, items: list<array<string, mixed>>}> $tree */
        $tree = Cache::remember(self::cacheKey(), self::TTL, static fn (): array => self::resolve());

        return $tree;
    }

    /**
     * Clé de cache de l'arbre (exposée pour l'exploitation et les tests).
     */
    public static function cacheKey(): string
    {
        $uris = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uris[] = $route->uri();
        }

        return 'navigation.tree.'.md5(serialize(config('navigation.groups', [])).'|'.implode(',', $uris));
    }

    /**
     * Purge l'arbre courant.
     */
    public static function forget(): void
    {
        Cache::forget(self::cacheKey());
    }

    /**
     * Arbre résolu : une entrée dont la route n'existe pas encore est
     * écartée (aucun lien mort), et un groupe devenu vide disparaît.
     *
     * @return list<array{label: string|null, items: list<array<string, mixed>>}>
     */
    private static function resolve(): array
    {
        /** @var list<array{label: string|null, items: list<array<string, mixed>>}> $groups */
        $groups = config('navigation.groups', []);

        $resolved = [];

        foreach ($groups as $group) {
            $items = array_values(array_filter(
                $group['items'] ?? [],
                static fn (array $item): bool => self::isReachable($item)
            ));

            if ($items === []) {
                continue;
            }

            // L'URL absolue et l'état « actif » dépendent de la requête
            // (hôte, schéma, route courante) : jamais mémorisés ici, ils
            // sont résolus à chaque rendu par forUser().
            $resolved[] = [
                'label' => $group['label'] ?? null,
                'items' => $items,
            ];
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function isItemAllowed(array $item, ?Authenticatable $user): bool
    {
        $permission = $item['permission'] ?? null;

        if ($permission === null) {
            return true;
        }

        // Fail-closed : sans utilisateur authentifié, aucune entrée
        // permissionnée n'est exposée. Le masquage n'est qu'un confort
        // d'affichage — la route reste protégée côté serveur.
        if ($user === null) {
            return false;
        }

        if ($user instanceof Authorizable) {
            return $user->can($permission);
        }

        return false;
    }

    /**
     * Une route existe-t-elle pour cette entrée de menu ?
     *
     * @param  array<string, mixed>  $item
     */
    private static function isReachable(array $item): bool
    {
        if (isset($item['route']) && Route::has((string) $item['route'])) {
            return true;
        }

        $path = trim((string) ($item['path'] ?? ''), '/');

        if ($path === '') {
            return false;
        }

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();

            if ($uri === $path || str_starts_with($uri, $path.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function href(array $item): string
    {
        $path = trim((string) ($item['path'] ?? ''), '/');

        if (isset($item['route']) && Route::has((string) $item['route'])) {
            return route((string) $item['route']);
        }

        return url($path);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function isActive(array $item): bool
    {
        if (isset($item['route']) && request()->routeIs((string) $item['route'])) {
            return true;
        }

        $path = trim((string) ($item['path'] ?? ''), '/');

        if ($path === '') {
            return false;
        }

        // Une entrée racine ne doit pas s'activer sur toutes ses sous-pages.
        return request()->is($path) || request()->is($path.'/*');
    }
}
