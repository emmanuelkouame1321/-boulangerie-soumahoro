<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Bakery;
use App\Models\Product;
use RuntimeException;

/**
 * Stock insuffisant pour l'opération demandée (cahier §28 et §41).
 *
 * Message destiné à l'utilisateur : jamais de détail technique. Exemple
 * exigé par le cahier §41 : « Il manque 3 sacs de farine. »
 */
final class InsufficientStockException extends RuntimeException
{
    public static function for(Product $product, Bakery $bakery, string $requested, string $available): self
    {
        $unit = (string) ($product->primaryUnit->symbol ?? '');

        $missing = self::format(self::subtract($requested, $available));

        return new self(sprintf(
            'Stock insuffisant : il manque %s %s de « %s » sur le site « %s » (disponible : %s %s).',
            $missing,
            $unit,
            $product->name,
            $bakery->name,
            self::format($available),
            $unit
        ));
    }

    public static function forReservation(Product $product, Bakery $bakery, string $requested, string $available): self
    {
        $unit = (string) ($product->primaryUnit->symbol ?? '');

        return new self(sprintf(
            'Réservation impossible : %s %s de « %s » demandés sur « %s », %s %s disponibles.',
            self::format($requested),
            $unit,
            $product->name,
            $bakery->name,
            self::format($available),
            $unit
        ));
    }

    private static function subtract(string $left, string $right): string
    {
        $result = bcsub($left, $right, 4);

        return bccomp($result, '0', 4) === -1 ? '0' : $result;
    }

    private static function format(string $value): string
    {
        $trimmed = rtrim(rtrim($value, '0'), '.');

        return $trimmed === '' || $trimmed === '-0' ? '0' : $trimmed;
    }
}
