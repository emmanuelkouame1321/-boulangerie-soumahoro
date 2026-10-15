<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Sites\BakeryContext;
use App\Models\StockTransfer;
use App\Models\User;

/**
 * Autorisation sur les transferts inter-sites (cahier §19.3 et §20).
 *
 * Un transfert relie **deux** sites : le contrôle de portée est donc
 * explicite (le modèle n'utilise pas `BelongsToBakery`). La résolution de
 * route est déjà restreinte aux sites accessibles à l'utilisateur
 * (`Route::bind('transfer', …)`), mais la Policy reverrouille l'accès,
 * et distingue l'expéditeur du destinataire :
 *
 *  - `send` n'est ouvert qu'à un utilisateur ayant accès au **site
 *    expéditeur** ;
 *  - `receive` qu'à un utilisateur ayant accès au **site destinataire**.
 *
 * Le Super-Admin passe avant la Policy (Gate::before), il n'est donc pas
 * concerné par ces restrictions de site.
 */
class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('transfer.view');
    }

    public function view(User $user, StockTransfer $transfer): bool
    {
        return $user->can('transfer.view') && $this->hasAccessToEitherSite($user, $transfer);
    }

    public function create(User $user): bool
    {
        return $user->can('transfer.create');
    }

    public function send(User $user, StockTransfer $transfer): bool
    {
        return $user->can('transfer.create')
            && $this->hasAccessTo($user, (int) $transfer->bakery_id);
    }

    public function receive(User $user, StockTransfer $transfer): bool
    {
        return $user->can('transfer.receive')
            && $this->hasAccessTo($user, (int) $transfer->destination_bakery_id);
    }

    public function cancel(User $user, StockTransfer $transfer): bool
    {
        return $user->can('transfer.create')
            && $this->hasAccessTo($user, (int) $transfer->bakery_id);
    }

    public function delete(User $user): bool
    {
        return false;
    }

    private function hasAccessToEitherSite(User $user, StockTransfer $transfer): bool
    {
        return $this->hasAccessTo($user, (int) $transfer->bakery_id)
            || $this->hasAccessTo($user, (int) $transfer->destination_bakery_id);
    }

    private function hasAccessTo(User $user, int $bakeryId): bool
    {
        return in_array($bakeryId, $this->accessibleSiteIds(), true);
    }

    /**
     * @return list<int>
     */
    private function accessibleSiteIds(): array
    {
        $context = app(BakeryContext::class);

        /** @var list<int> $ids */
        $ids = $context->availableBakeries()->pluck('id')->all();

        return $ids;
    }
}
