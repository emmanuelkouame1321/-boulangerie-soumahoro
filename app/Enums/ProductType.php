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

    /**
     * Groupe d'affichage (cahier §4.1) : « products » = produit fini,
     * « materials » = tout le reste (matières premières, consommables,
     * emballages, ressources, semi-finis).
     */
    public function group(): string
    {
        return $this === self::FinishedGood ? 'products' : 'materials';
    }

    /**
     * Préfixe du code produit généré (cahier §4.2). Ex. `PF-0001` pour un
     * produit fini, `MP-0001` pour une matière première.
     */
    public function skuPrefix(): string
    {
        return match ($this) {
            self::RawMaterial => 'MP',
            self::SemiFinished => 'SF',
            self::FinishedGood => 'PF',
            self::Consumable => 'CONS',
            self::Packaging => 'EMB',
            self::Resource => 'RES',
        };
    }

    /**
     * @return list<string>
     */
    public static function groups(): array
    {
        return ['products', 'materials'];
    }

    /**
     * @return list<self>
     */
    public static function forGroup(string $group): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $type): bool => $type->group() === $group,
        ));
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
