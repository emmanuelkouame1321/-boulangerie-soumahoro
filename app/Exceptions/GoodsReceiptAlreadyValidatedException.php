<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\GoodsReceipt;
use RuntimeException;

/**
 * Une réception ne peut être validée qu'une fois (cahier §11.3 et §29).
 *
 * Message destiné à l'utilisateur : jamais de détail technique.
 */
final class GoodsReceiptAlreadyValidatedException extends RuntimeException
{
    public static function for(GoodsReceipt $receipt): self
    {
        return new self(sprintf(
            'La réception « %s » a déjà été validée : elle ne peut pas être validée une seconde fois.',
            $receipt->reference
        ));
    }
}
