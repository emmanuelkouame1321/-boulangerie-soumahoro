<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Calcul décimal exact (BCMath).
 *
 * Les quantités et montants métier ne doivent jamais transiter par un
 * `float` : la précision ne dépend pas de la représentation binaire.
 * Toutes les opérations de stock et d'unités passent par ici.
 */
final class Decimal
{
    /** Nombre de décimales des quantités de stock. */
    public const SCALE = 4;

    /**
     * Normalise une entrée (int, float, chaîne) en chaîne décimale
     * exploitable par BCMath.
     */
    public static function normalize(int|float|string $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return rtrim(rtrim(sprintf('%.12F', $value), '0'), '.') ?: '0';
        }

        return trim($value) === '' ? '0' : trim($value);
    }

    /**
     * Retire les zéros inutiles : « 50.0000 » → « 50 », « 0.5000 » → « 0.5 ».
     */
    public static function trim(string $value): string
    {
        if (! str_contains($value, '.')) {
            return $value === '-0' ? '0' : $value;
        }

        $trimmed = rtrim(rtrim($value, '0'), '.');

        return $trimmed === '' || $trimmed === '-0' ? '0' : $trimmed;
    }

    /** Valeur absolue, arrondie à l'échelle des quantités. */
    public static function abs(int|float|string $value, int $scale = self::SCALE): string
    {
        $normalized = self::normalize($value);

        $absolute = bccomp($normalized, '0', $scale) === -1
            ? bcmul($normalized, '-1', $scale)
            : $normalized;

        return self::trim($absolute);
    }

    /** Somme exacte, arrondie à l'échelle des quantités. */
    public static function add(int|float|string $left, int|float|string $right, int $scale = self::SCALE): string
    {
        return self::trim(bcadd(self::normalize($left), self::normalize($right), $scale));
    }

    /** Différence exacte, arrondie à l'échelle des quantités. */
    public static function subtract(int|float|string $left, int|float|string $right, int $scale = self::SCALE): string
    {
        return self::trim(bcsub(self::normalize($left), self::normalize($right), $scale));
    }

    /** Produit exact, arrondi à l'échelle des quantités. */
    public static function multiply(int|float|string $left, int|float|string $right, int $scale = self::SCALE): string
    {
        return self::trim(bcmul(self::normalize($left), self::normalize($right), $scale));
    }

    /** Compare deux valeurs (−1, 0, 1). */
    public static function compare(int|float|string $left, int|float|string $right, int $scale = self::SCALE): int
    {
        return bccomp(self::normalize($left), self::normalize($right), $scale);
    }

    public static function isPositive(int|float|string $value, int $scale = self::SCALE): bool
    {
        return bccomp(self::normalize($value), '0', $scale) === 1;
    }

    public static function isNegative(int|float|string $value, int $scale = self::SCALE): bool
    {
        return bccomp(self::normalize($value), '0', $scale) === -1;
    }
}
