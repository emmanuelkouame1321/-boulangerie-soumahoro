<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Statut d'une vente (cahier §14).
 *
 * Une vente validée a déduit le stock de produits finis ; une vente
 * annulée est un changement de statut, jamais une suppression.
 */
enum SaleStatus: string
{
    case Validated = 'validated';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return (string) config(
            "boulangerie.statuses.sale.{$this->value}.label",
            Str::headline($this->value)
        );
    }

    public function tone(): string
    {
        return (string) config(
            "boulangerie.statuses.sale.{$this->value}.tone",
            'neutral'
        );
    }

    public function isValidated(): bool
    {
        return $this === self::Validated;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
