<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Cycle de vie d'une réception fournisseur (cahier §11.3).
 *
 * Une réception validée a créé ses mouvements d'entrée de stock ; elle ne
 * peut plus être validée une seconde fois (idempotence de l'opération).
 */
enum GoodsReceiptStatus: string
{
    case Draft = 'draft';
    case Validated = 'validated';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return (string) config(
            "boulangerie.statuses.receipt.{$this->value}.label",
            Str::headline($this->value)
        );
    }

    public function tone(): string
    {
        return (string) config(
            "boulangerie.statuses.receipt.{$this->value}.tone",
            'neutral'
        );
    }

    public function isValidated(): bool
    {
        return $this === self::Validated;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
