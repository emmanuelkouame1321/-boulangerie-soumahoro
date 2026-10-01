<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * Cycle de vie d'une commande fournisseur (cahier §11.1).
 *
 * Le statut « en attente » désigne une commande en cours de préparation ;
 * « validée » correspond à une réception effectivement contrôlée et
 * intégrée au stock.
 */
enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Ordered = 'ordered';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Validated = 'validated';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return (string) config(
            "boulangerie.statuses.order.{$this->value}.label",
            Str::headline($this->value)
        );
    }

    public function tone(): string
    {
        return (string) config(
            "boulangerie.statuses.order.{$this->value}.tone",
            'neutral'
        );
    }

    /**
     * Une commande peut encore être réceptionnée.
     */
    public function isReceivable(): bool
    {
        return match ($this) {
            self::Pending, self::Ordered, self::PartiallyReceived => true,
            default => false,
        };
    }

    /**
     * Statuts terminaux : plus aucune action opérationnelle possible.
     */
    public function isClosed(): bool
    {
        return match ($this) {
            self::Validated, self::Cancelled => true,
            default => false,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
