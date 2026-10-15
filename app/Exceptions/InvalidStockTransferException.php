<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\StockTransfer;
use RuntimeException;

/**
 * Transfert inter-sites invalide (cahier §19.3).
 *
 * Message destiné à l'utilisateur : jamais de détail technique. Les
 * erreurs de statut signalent une action impossible dans l'état courant
 * (rejouer une expédition, recevoir un brouillon, annuler un transfert
 * déjà expédié).
 */
final class InvalidStockTransferException extends RuntimeException
{
    public static function sameSite(): self
    {
        return new self('Un transfert doit relier deux boulangeries différentes.');
    }

    public static function nonPositiveQuantity(): self
    {
        return new self('La quantité transférée doit être strictement positive.');
    }

    public static function batchNotInSource(): self
    {
        return new self('Le lot choisi n’appartient pas à la boulangerie expéditrice.');
    }

    public static function notDraft(StockTransfer $transfer): self
    {
        return new self("Le transfert {$transfer->reference} n'est plus un brouillon : il ne peut plus être modifié.");
    }

    public static function notSent(StockTransfer $transfer): self
    {
        return new self("Le transfert {$transfer->reference} n'a pas encore été expédié.");
    }

    public static function alreadyFinal(StockTransfer $transfer): self
    {
        return new self("Le transfert {$transfer->reference} est déjà {$transfer->status->label()}.");
    }
}
