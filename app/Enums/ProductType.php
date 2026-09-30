<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Types de produits (cahier §4.1).
 */
enum ProductType: string
{
    case RawMaterial = 'raw_material';
    case SemiFinished = 'semi_finished';
    case FinishedGood = 'finished_good';
    case Consumable = 'consumable';
    case Packaging = 'packaging';
    case Resource = 'resource';

    public function label(): string
    {
        return match ($this) {
            self::RawMaterial => 'Matière première',
            self::SemiFinished => 'Produit semi-fini',
            self::FinishedGood => 'Produit fini',
            self::Consumable => 'Consommable',
            self::Packaging => 'Emballage',
            self::Resource => 'Ressource de production',
        };
    }

    public function isStockable(): bool
    {
        return $this !== self::Resource;
    }

    public function isSellable(): bool
    {
        return $this === self::FinishedGood;
    }
}
