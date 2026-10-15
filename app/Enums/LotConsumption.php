<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Motif de consommation d'un lot (cahier §8 et §19.3).
 *
 * Le motif détermine le compteur incrémenté sur le lot — la somme
 * `disponible + vendu + perdu + transféré` reste égale à la quantité
 * initiale — et si les lots expirés sont consommables : une vente ne sort
 * jamais un lot expiré (cahier §41), une perte oui, un transfert non
 * (on ne déplace pas vers un autre site un produit qui n'est plus vendable).
 */
enum LotConsumption: string
{
    case Sold = 'sold';
    case Lost = 'lost';
    case Transferred = 'transferred';

    /**
     * Un lot expiré peut-il être consommé pour ce motif ?
     */
    public function includesExpired(): bool
    {
        return $this === self::Lost;
    }
}
