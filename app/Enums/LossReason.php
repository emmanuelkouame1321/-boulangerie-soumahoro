<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Motifs de perte (cahier §8.2, §15 et §22).
 *
 * Les libellés viennent de `config('boulangerie.loss_reasons')` : les
 * motifs restent administrables sans redéploiement.
 */
enum LossReason: string
{
    case Expiry = 'expiry';
    case Unsold = 'unsold';
    case ProductionDefect = 'production_defect';
    case Damage = 'damage';
    case Other = 'other';

    public function label(): string
    {
        return (string) config(
            "boulangerie.loss_reasons.{$this->value}",
            Str::headline($this->value)
        );
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $reason): string => $reason->value, self::cases());
    }
}
