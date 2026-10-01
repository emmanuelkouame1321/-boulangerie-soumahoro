<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Statut d'une ristourne (cahier §13).
 *
 * Le cahier décrit une ristourne théorique, une ristourne validée puis un
 * montant versé/crédité avec une date de règlement. Le statut matérialise
 * ces trois étapes ; une ristourne validée n'est plus recalculée.
 */
enum RebateStatus: string
{
    case Projected = 'projected';
    case Validated = 'validated';
    case Paid = 'paid';

    public function label(): string
    {
        return (string) config(
            "boulangerie.statuses.rebate.{$this->value}.label",
            Str::headline($this->value)
        );
    }

    public function tone(): string
    {
        return (string) config(
            "boulangerie.statuses.rebate.{$this->value}.tone",
            'neutral'
        );
    }

    public function isLocked(): bool
    {
        return $this !== self::Projected;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
