<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Données de configuration dérivées (cahier §48).
 *
 * `config/*.php` est chargé une fois par requête : le coût n'est pas la
 * lecture des tableaux, mais leur mise en forme répétée — plan de
 * permissions groupé par module, libellés et permissions par rôle — que
 * plusieurs écrans (rôles, utilisateurs) reconstruisent à chaque rendu.
 *
 * Ces structures sont mémorisées dans le cache sous une clé indexée par
 * une empreinte de `config/boulangerie.php` : modifier la configuration
 * produit une nouvelle clé, donc aucune purge manuelle n'est nécessaire.
 * Le TTL borne la durée de vie des anciennes clés.
 */
final class Configuration
{
    /**
     * Durée de vie d'une entrée dérivée (24 h) : la clé changeant avec le
     * contenu, ce délai n'est qu'un nettoyage des clés devenues inutiles.
     */
    public const TTL = 86_400;

    /**
     * Rôles applicatifs déclarés, avec leur libellé et leur plan de
     * permissions (`config('boulangerie.roles')`).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function roles(): array
    {
        /** @var array<string, array<string, mixed>> $roles */
        $roles = self::cached('roles', static fn (): array => (array) config('boulangerie.roles', []));

        return $roles;
    }

    /**
     * Libellé et permissions d'un rôle, avec les replis de l'énumération
     * `App\Enums\Role` (titre du nom, aucune permission).
     *
     * @return array{label: string, permissions: list<string>}
     */
    public static function role(string $name): array
    {
        /** @var array<string, mixed> $definition */
        $definition = self::roles()[$name] ?? [];

        /** @var list<string> $permissions */
        $permissions = array_values((array) ($definition['permissions'] ?? []));

        return [
            'label' => (string) ($definition['label'] ?? Str::headline($name)),
            'permissions' => $permissions,
        ];
    }

    /**
     * Catalogue complet des permissions (`config('boulangerie.permissions')`).
     *
     * @return list<string>
     */
    public static function permissionCatalogue(): array
    {
        /** @var list<string> $permissions */
        $permissions = self::cached('permissions', static function (): array {
            /** @var array<int, mixed> $configured */
            $configured = (array) config('boulangerie.permissions', []);

            return array_values(array_map(static fn (mixed $permission): string => (string) $permission, $configured));
        });

        return $permissions;
    }

    /**
     * Permissions du catalogue groupées par module (préfixe avant le point),
     * modules triés alphabétiquement.
     *
     * @return array<string, list<string>>
     */
    public static function permissionGroups(): array
    {
        /** @var array<string, list<string>> $groups */
        $groups = self::cached('permission-groups', static function (): array {
            $grouped = [];

            foreach (self::permissionCatalogue() as $permission) {
                $module = explode('.', $permission)[0];
                $grouped[$module][] = $permission;
            }

            ksort($grouped);

            return $grouped;
        });

        return $groups;
    }

    /**
     * Clé de cache d'une structure dérivée (exposée pour l'exploitation
     * et les tests).
     */
    public static function cacheKey(string $name): string
    {
        return 'boulangerie.configuration.'.$name.'.'.self::fingerprint();
    }

    /**
     * Purge les structures dérivées courantes.
     */
    public static function forget(): void
    {
        foreach (['roles', 'permissions', 'permission-groups'] as $name) {
            Cache::forget(self::cacheKey($name));
        }
    }

    /**
     * @param  callable(): array<array-key, mixed>  $callback
     * @return array<array-key, mixed>
     */
    private static function cached(string $name, callable $callback): array
    {
        /** @var array<array-key, mixed> $value */
        $value = Cache::remember(self::cacheKey($name), self::TTL, $callback);

        return $value;
    }

    /**
     * Empreinte du contenu configuré : toute modification de
     * `config/boulangerie.php` invalide les entrées sans purge manuelle.
     * Les identifiants d'amorçage sont exclus de la clé.
     */
    private static function fingerprint(): string
    {
        $config = (array) config('boulangerie', []);

        unset($config['super_admin_seed']);

        return md5(serialize($config));
    }
}
