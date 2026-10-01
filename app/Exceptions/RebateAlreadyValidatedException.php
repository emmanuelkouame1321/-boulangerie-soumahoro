<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\WholesalerRebate;
use RuntimeException;

/**
 * Une ristourne validée ou versée n'est plus recalculée (cahier §13 et
 * §53-11 : les données historiques ne sont pas écrasées).
 */
final class RebateAlreadyValidatedException extends RuntimeException
{
    public static function for(WholesalerRebate $rebate): self
    {
        return new self(sprintf(
            'La ristourne %d de « %s » est déjà %s : elle ne peut plus être recalculée.',
            $rebate->period_year,
            $rebate->wholesaler->name,
            mb_strtolower($rebate->status->label())
        ));
    }
}
