<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;

/**
 * Construction du menu applicatif (Design System §10, §52).
 *
 * Le menu est filtré côté serveur selon les permissions : un utilisateur
 * ne doit jamais voir une entrée qu'il ne peut pas utiliser, et la route
 * elle-même reste protégée par une Policy / un middleware.
 */
final class Navigation
{
    /**
     * @return list<array{label: string|null, items: list<array<string, mixed>>}>
     */
    public static function forUser(?Authenticatable $user): array
    {
        /** @var list<array{label: string|null, items: list<array<string, mixed>>}> $groups */
        $groups = config('navigation.groups', []);

        $filtered = [];

        foreach ($groups as $group) {
            $items = array_values(array_filter(
                $group['items'] ?? [],
                static fn (array $item): bool => self::isItemAllowed($item, $user)
            ));

            if ($items === []) {
                continue;
            }

            $filtered[] = [
                'label' => $group['label'] ?? null,
                'items' => array_map(
                    static fn (array $item): array => $item + ['href' => self::href($item), 'active' => self::isActive($item)],
                    $items
                ),
            ];
        }

        return $filtered;
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

        // Tant que l'authentification n'est pas en place (Phase 1 du planning),
        // le socle affiche la structure complète pour permettre la validation
        // de la mise en page. Les routes restent à protéger en Phase 1.
        if ($user === null) {
            return true;
        }

        if ($user instanceof Authorizable) {
            return $user->can($permission);
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
