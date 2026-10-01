<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\ProductionBatch;
use App\Support\Decimal;
use RuntimeException;

/**
 * La quantité déclarée dépasse le reste disponible du lot (cahier §8).
 */
final class InsufficientLotException extends RuntimeException
{
    public static function for(ProductionBatch $batch, string $requested): self
    {
        return new self(sprintf(
            'Quantité demandée : %s — le lot %s ne contient plus que %s.',
            Decimal::trim($requested),
            $batch->reference,
            Decimal::trim((string) $batch->available_quantity)
        ));
    }
}
