<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

use App\Enums\StockAlertType;
use App\Models\NotificationPreference;
use App\Models\StockAlert;
use App\Models\User;
use App\Notifications\StockAlertMail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Acheminement des alertes vers les canaux configurés (cahier §22).
 *
 * - canal `internal` : l'écran /notifications affiche l'alerte (donc
 *   rien à faire ici) ;
 * - canal `email` : notification mise en queue vers les utilisateurs
 *   affectés au site ayant la permission `alert.view` (§53-13).
 *
 * La préférence du site (`notification_preferences`) surcharge les
 * valeurs par défaut de `config('boulangerie.notification_channels')`.
 */
class AlertNotifier
{
    /**
     * Une alerte vient de s'ouvrir : notifie les canaux actifs.
     */
    public function alertOpened(StockAlert $alert): void
    {
        $bakeryId = $alert->getAttribute('bakery_id');

        if ($bakeryId === null || ! $this->wants((int) $bakeryId, $alert->type, 'email')) {
            return;
        }

        $users = $this->recipients((int) $bakeryId);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new StockAlertMail($alert));
    }

    /**
     * Le canal est-il actif pour ce site et ce type ? La ligne de
     * préférence du site l'emporte, sinon la configuration par défaut.
     */
    public function wants(int $bakeryId, StockAlertType $type, string $channel): bool
    {
        $preference = NotificationPreference::query()
            ->where('bakery_id', $bakeryId)
            ->where('type', $type->value)
            ->first();

        if ($preference !== null) {
            return (bool) $preference->getAttribute($channel);
        }

        $defaults = (array) config(
            "boulangerie.notification_channels.{$type->value}",
            (array) config('boulangerie.notification_channels.default', []),
        );

        return (bool) ($defaults[$channel] ?? false);
    }

    /**
     * Utilisateurs affectés au site ayant la permission `alert.view`
     * (le Super-Admin est couvert par son wildcard).
     *
     * @return Collection<int, User>
     */
    public function recipients(int $bakeryId): Collection
    {
        /** @var Collection<int, User> $users */
        $users = User::query()
            ->whereHas('bakeries', static function ($query) use ($bakeryId): void {
                $query->where('bakeries.id', $bakeryId);
            })
            ->get()
            ->filter(static fn (User $user): bool => $user->can('alert.view'))
            ->values();

        return $users;
    }
}
