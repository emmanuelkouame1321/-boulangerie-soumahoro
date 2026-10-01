<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Statut d'une période de paie (cahier §17).
 *
 * La période est préparée (brouillon), validée puis versée : dès la
 * validation, l'historique est figé et n'est plus recalculé (cahier
 * §53-11).
 */
enum PayrollStatus: string
{
    case Draft = 'draft';
    case Validated = 'validated';
    case Paid = 'paid';

    public function label(): string
    {
        return (string) config(
            "boulangerie.statuses.payroll.{$this->value}.label",
            Str::headline($this->value)
        );
    }

    public function tone(): string
    {
        return (string) config(
            "boulangerie.statuses.payroll.{$this->value}.tone",
            'neutral'
        );
    }

    public function isLocked(): bool
    {
        return $this !== self::Draft;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
