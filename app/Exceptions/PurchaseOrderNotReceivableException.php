<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\PurchaseOrder;
use RuntimeException;

/**
 * Une commande ne peut être réceptionnée que dans un statut compatible
 * (cahier §11.1 et §11.3).
 */
final class PurchaseOrderNotReceivableException extends RuntimeException
{
    public static function for(PurchaseOrder $order): self
    {
        return new self(sprintf(
            'La commande « %s » est « %s » : elle ne peut pas être réceptionnée.',
            $order->reference,
            $order->status->label()
        ));
    }

    public static function empty(PurchaseOrder $order): self
    {
        return new self(sprintf(
            'Aucune quantité reçue n’a été saisie pour la commande « %s ».',
            $order->reference
        ));
    }
}
