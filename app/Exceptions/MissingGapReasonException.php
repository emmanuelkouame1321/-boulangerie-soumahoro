<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\ProductionOrder;
use RuntimeException;

/**
 * Un écart de rendement non nul doit être motivé (cahier §7.4).
 */
final class MissingGapReasonException extends RuntimeException
{
    public static function for(ProductionOrder $order, string $gap): self
    {
        return new self(sprintf(
            'Un écart de %s unités a été constaté sur la production « %s » : indiquez un motif.',
            $gap,
            $order->reference
        ));
    }
}
