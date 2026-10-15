<?php

declare(strict_types=1);

namespace App\Enums;

use App\Support\Configuration;

/**
 * Rôles applicatifs (cahier §20).
 *
 * Les libellés et le plan de permissions vivent dans config/boulangerie.php,
 * lus via App\Support\Configuration (structures dérivées mises en cache,
 * cahier §48). Ne jamais écrire un nom de rôle en dur ailleurs : utiliser
 * cette enum.
 */
enum Role: string
{
    case SuperAdmin = 'super-admin';
    case ProductionManager = 'production-manager';
    case SalesManager = 'sales-manager';
    case Cashier = 'cashier';

    public function label(): string
    {
        return Configuration::role($this->value)['label'];
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return Configuration::role($this->value)['permissions'];
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }
}
