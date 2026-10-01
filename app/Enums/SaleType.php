<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Type de vente (cahier §12 et §14).
 *
 * Une vente au détail vise les particuliers ; une vente grossiste applique
 * le prix grossiste. La vente grossiste nominative est traitée en Phase 7
 * (module Grossistes).
 */
enum SaleType: string
{
    case Retail = 'retail';
    case Wholesale = 'wholesale';

    public function label(): string
    {
        return (string) config(
            "boulangerie.sale_types.{$this->value}",
            Str::headline($this->value)
        );
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
