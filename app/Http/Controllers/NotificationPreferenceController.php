<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Notifications\AlertNotifier;
use App\Domain\Sites\BakeryContext;
use App\Enums\StockAlertType;
use App\Models\NotificationPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Préférences de notification par site (cahier §22 et §24).
 *
 * Deux canaux par type d'alerte : interne (écran /notifications) et
 * email (queue). La route est protégée par `permission:settings.view` ;
 * un site doit être sélectionné pour configurer ses préférences.
 */
class NotificationPreferenceController extends Controller
{
    public function index(Request $request, BakeryContext $context, AlertNotifier $notifier): View
    {
        $bakery = $context->current();

        return view('settings.notifications.index', [
            'bakery' => $bakery,
            'types' => StockAlertType::cases(),
            'channels' => $bakery === null ? [] : $this->channels($notifier, (int) $bakery->getKey()),
        ]);
    }

    public function update(Request $request, BakeryContext $context): RedirectResponse
    {
        $bakery = $context->current();

        if ($bakery === null) {
            return back()->withErrors(['site' => 'Sélectionnez une boulangerie avant de configurer ses notifications.']);
        }

        /** @var array{internal?: array<string, string>, email?: array<string, string>} $data */
        $data = $request->validate([
            'internal' => ['array'],
            'email' => ['array'],
        ]);

        foreach (StockAlertType::cases() as $type) {
            NotificationPreference::query()->updateOrCreate(
                ['bakery_id' => $bakery->getKey(), 'type' => $type->value],
                [
                    'internal' => isset($data['internal'][$type->value]),
                    'email' => isset($data['email'][$type->value]),
                ],
            );
        }

        return redirect()
            ->route('settings.notifications.index')
            ->with('status', "Les notifications de « {$bakery->name} » ont été enregistrées.");
    }

    /**
     * État actuel des canaux, préférence du site ou défaut de config.
     *
     * @return array<string, array{internal: bool, email: bool}>
     */
    private function channels(AlertNotifier $notifier, int $bakeryId): array
    {
        $channels = [];

        foreach (StockAlertType::cases() as $type) {
            $channels[$type->value] = [
                'internal' => $notifier->wants($bakeryId, $type, 'internal'),
                'email' => $notifier->wants($bakeryId, $type, 'email'),
            ];
        }

        return $channels;
    }
}
