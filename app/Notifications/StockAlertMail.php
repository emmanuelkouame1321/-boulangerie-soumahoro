<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\StockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email d'alerte de stock (cahier §22 — canal « email »).
 *
 * Déclenchée par `AlertNotifier` uniquement lorsque le canal est actif
 * pour le site et le type d'alerte. `ShouldQueue` : l'envoi passe par la
 * queue (§53-13), la ligne de job n'est visible qu'après le commit de la
 * transaction qui a ouvert l'alerte.
 */
class StockAlertMail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly StockAlert $alert) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $type = $this->alert->type->label();
        $product = data_get($this->alert, 'product.name') ?? 'Produit';
        $site = data_get($this->alert, 'bakery.name') ?? 'Site inconnu';
        $message = $this->alert->message ?? '';

        return (new MailMessage)
            ->subject("[{$site}] Alerte {$type} — {$product}")
            ->line("Site : {$site}")
            ->line("Alerte : {$type}")
            ->line("Produit : {$product}")
            ->when($message !== '', fn (MailMessage $mail) => $mail->line($message))
            ->action('Ouvrir le centre d’alertes', route('notifications.index'))
            ->line('Vous recevez cet email car vous avez la permission de consulter les alertes de ce site.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'alert_id' => $this->alert->getKey(),
            'type' => $this->alert->type->value,
        ];
    }
}
