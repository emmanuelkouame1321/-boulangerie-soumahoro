<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cycle de vie d'un transfert inter-sites (cahier §19.3).
 *
 * `draft`   : préparé, aucun mouvement de stock — annulable sans trace ;
 * `sent`    : le stock a quitté le site expéditeur (`transfer_out`) ;
 * `received`: le stock est arrivé chez le destinataire (`transfer_in`) ;
 * `cancelled`: abandonné avant expédition.
 *
 * `sent` et `received` sont définitifs : un transfert expédié ne se
 * supprime pas, il se corrige par un transfert inverse (cahier §53-11).
 */
enum StockTransferStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Sent => 'Expédié',
            self::Received => 'Reçu',
            self::Cancelled => 'Annulé',
        };
    }

    /**
     * Sémantique visuelle du composant <x-ui.status-badge />.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Sent => 'warning',
            self::Received => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Statut sur lequel on ne revient plus (sauf transfert inverse).
     */
    public function isFinal(): bool
    {
        return $this === self::Received || $this === self::Cancelled;
    }
}
