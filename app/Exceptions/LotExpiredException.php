<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

/**
 * Un lot expiré ne peut plus être vendu (cahier §41).
 */
final class LotExpiredException extends RuntimeException
{
    public static function for(Product $product): self
    {
        return new self(sprintf(
            '« %s » : ce lot ne peut plus être vendu car sa date limite est dépassée.',
            $product->name
        ));
    }
}
