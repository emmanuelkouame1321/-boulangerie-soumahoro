<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Nature d'un prix produit (cahier §4.2 et §24).
 */
enum PriceType: string
{
    case Purchase = 'purchase';
    case Sale = 'sale';
    case Wholesale = 'wholesale';

    public function label(): string
    {
        return match ($this) {
            self::Purchase => 'Prix d’achat',
            self::Sale => 'Prix de vente',
            self::Wholesale => 'Prix grossiste',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }

    public static function fromName(string $name): self
    {
        return self::from(Str::lower($name));
    }
}
