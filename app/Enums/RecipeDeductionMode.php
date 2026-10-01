<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Mode de déduction d'une ressource de recette (cahier §5.3).
 *
 * Le cahier distingue quatre natures de ressource :
 *  - `quantity` : stockée physiquement, la production déduit le stock ;
 *  - `monetary` : consommée selon un coût, sans mouvement de stock ;
 *  - `cumulated` : cumulée (fagot, glace…), sans mouvement de stock ;
 *  - `manual` : quantité réelle saisie lors de la production.
 */
enum RecipeDeductionMode: string
{
    case Quantity = 'quantity';
    case Monetary = 'monetary';
    case Cumulated = 'cumulated';
    case Manual = 'manual';

    public function label(): string
    {
        return (string) config(
            "boulangerie.recipe_deduction_modes.{$this->value}",
            Str::headline($this->value)
        );
    }

    /**
     * La production retire-t-elle de la quantité du stock ?
     *
     * Seules les ressources physiques (déduction directe et saisie réelle)
     * génèrent un mouvement de sortie. Les ressources monétaires et
     * cumulées ne sont pas stockées.
     */
    public function deductsStock(): bool
    {
        return match ($this) {
            self::Quantity, self::Manual => true,
            self::Monetary, self::Cumulated => false,
        };
    }

    /**
     * Le coût de la ressource est-il porté par un montant configuré plutôt
     * que par le prix d'achat du produit ?
     */
    public function isCostBased(): bool
    {
        return match ($this) {
            self::Monetary, self::Cumulated => true,
            default => false,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $mode): string => $mode->value, self::cases());
    }
}
