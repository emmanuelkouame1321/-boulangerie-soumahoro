<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\ProductionOrder;
use RuntimeException;

/**
 * Un ordre de production validé a déjà consommé les matières et créé le
 * lot : il ne peut pas être validé une seconde fois (cahier §27 et §29).
 */
final class ProductionAlreadyValidatedException extends RuntimeException
{
    public static function for(ProductionOrder $order): self
    {
        return new self(sprintf(
            'La production « %s » a déjà été validée : elle ne peut pas être validée une seconde fois.',
            $order->reference
        ));
    }
}
