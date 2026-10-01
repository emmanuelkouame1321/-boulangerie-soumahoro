<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Base de calcul d'une ristourne (cahier §13).
 *
 * Le cahier impose une règle administrable pouvant reposer sur la quantité,
 * le chiffre d'affaires, une famille de produits ou une combinaison de
 * critères. Aucun palier n'est codé en dur : il vit dans `wholesaler_rules`.
 */
enum RebateBasis: string
{
    case Quantity = 'quantity';
    case Revenue = 'revenue';
    case Family = 'family';
    case Combination = 'combination';

    public function label(): string
    {
        return (string) config(
            "boulangerie.rebate_bases.{$this->value}",
            Str::headline($this->value)
        );
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $basis): string => $basis->value, self::cases());
    }
}
