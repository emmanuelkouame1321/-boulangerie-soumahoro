<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Rôles applicatifs (cahier §20).
 *
 * Les libellés et le plan de permissions vivent dans config/boulangerie.php.
 * Ne jamais écrire un nom de rôle en dur ailleurs : utiliser cette enum.
 */
enum Role: string
{
    case SuperAdmin = 'super-admin';
    case ProductionManager = 'production-manager';
    case SalesManager = 'sales-manager';
    case Cashier = 'cashier';

    public function label(): string
    {
        return config("boulangerie.roles.{$this->value}.label", Str::headline($this->value));
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        /** @var list<string> $permissions */
        $permissions = config("boulangerie.roles.{$this->value}.permissions", []);

        return $permissions;
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
