<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Cycle de vie d'un ordre de production (cahier §7).
 *
 * Un ordre naît en brouillon, passe en attente puis en cours lorsqu'il est
 * engagé, et se termine validé (le stock a bougé) ou annulé. Un ordre
 * validé n'est jamais rejoué.
 */
enum ProductionOrderStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Validated = 'validated';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return (string) config(
            "boulangerie.statuses.production.{$this->value}.label",
            Str::headline($this->value)
        );
    }

    public function tone(): string
    {
        return (string) config(
            "boulangerie.statuses.production.{$this->value}.tone",
            'neutral'
        );
    }

    /**
     * Un ordre peut encore être validé (le stock n'a pas encore bougé).
     */
    public function canValidate(): bool
    {
        return match ($this) {
            self::Draft, self::Pending, self::InProgress => true,
            default => false,
        };
    }

    /**
     * Statuts terminaux : plus aucune action opérationnelle possible.
     */
    public function isClosed(): bool
    {
        return match ($this) {
            self::Validated, self::Cancelled => true,
            default => false,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
