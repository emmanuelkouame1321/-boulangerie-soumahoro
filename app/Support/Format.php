<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Formatage centralisé des montants et quantités (Design System §65, §60.7).
 *
 * Aucune vue ne doit formater un nombre elle-même : utiliser Format::money(),
 * Format::number() ou Format::quantity(). Le libellé de devise vient de
 * config('boulangerie.currency.label').
 */
final class Format
{
    public static function money(int|float|string|null $amount, bool $withCurrency = true): string
    {
        $formatted = self::number($amount);

        if (! $withCurrency) {
            return $formatted;
        }

        return $formatted.' '.config('boulangerie.currency.label', 'FCFA');
    }

    public static function number(int|float|string|null $value, int $decimals = 0): string
    {
        if ($value === null || $value === '') {
            return $decimals > 0 ? '0,'.str_repeat('0', $decimals) : '0';
        }

        $normalized = (float) $value;

        $formatted = number_format($normalized, $decimals, ',', ' ');

        // Espace insécable étroite pour que le montant ne se coupe jamais en fin de ligne.
        return str_replace(' ', "\u{202F}", $formatted);
    }

    /**
     * Quantité + symbole d'unité, ex. « 24 sacs ».
     */
    public static function quantity(int|float|string|null $value, ?string $unitCode = null, int $decimals = 0): string
    {
        $formatted = self::number($value, $decimals);

        if ($unitCode === null) {
            return $formatted;
        }

        $symbol = config("boulangerie.units.{$unitCode}.symbol", $unitCode);

        return "{$formatted} {$symbol}";
    }

    /**
     * Quantité de stock : décimales significatives, sans zéro inutile
     * (24 → « 24 », 0.5000 → « 0,5 »).
     */
    public static function stock(int|float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $trimmed = rtrim(rtrim((string) $value, '0'), '.');

        if ($trimmed === '' || $trimmed === '-0') {
            $trimmed = '0';
        }

        $decimals = str_contains($trimmed, '.') ? strlen(substr($trimmed, (int) strpos($trimmed, '.') + 1)) : 0;

        return self::number($trimmed, $decimals);
    }

    /**
     * Écart signé, ex. « -18 » ou « +4 ».
     */
    public static function signed(int|float|string|null $value, int $decimals = 0): string
    {
        $normalized = (float) ($value ?? 0);

        $prefix = $normalized > 0 ? '+' : '';

        return $prefix.self::number($normalized, $decimals);
    }

    public static function percent(int|float|string|null $value, int $decimals = 0): string
    {
        return self::number($value, $decimals).' %';
    }
}
