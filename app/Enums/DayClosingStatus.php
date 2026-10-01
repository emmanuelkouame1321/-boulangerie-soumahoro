<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Statut d'une clôture journalière (cahier §15).
 */
enum DayClosingStatus: string
{
    case Validated = 'validated';

    public function label(): string
    {
        return (string) config(
            "boulangerie.statuses.day_closing.{$this->value}.label",
            Str::headline($this->value)
        );
    }

    public function tone(): string
    {
        return (string) config(
            "boulangerie.statuses.day_closing.{$this->value}.tone",
            'neutral'
        );
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
