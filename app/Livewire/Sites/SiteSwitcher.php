<?php

declare(strict_types=1);

namespace App\Livewire\Sites;

use App\Domain\Sites\BakeryContext;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Sélecteur de boulangerie (Design System §12, cahier §19.2).
 *
 * Composant critique : le contexte de site doit toujours être visible et
 * son changement explicite. On ne change jamais de site silencieusement
 * pendant une saisie — d'où la redirection vers le tableau de bord après
 * bascule, qui évite de laisser l'utilisateur sur un formulaire ouvert
 * dans le mauvais contexte.
 */
class SiteSwitcher extends Component
{
    public function select(int $bakeryId): void
    {
        $context = app(BakeryContext::class);

        if (! auth()->user() instanceof User) {
            return;
        }

        if ($bakeryId === BakeryContext::ALL_SITES) {
            // La vision consolidée est réservée au Super-Admin (cahier §19.2).
            abort_unless($context->allowsAllSites(), 403);

            $context->switchTo(null);

            // Rechargement complet volontaire : garantir qu'aucun état de
            // composant ne survit au changement de contexte de site.
            $this->redirect(route('dashboard'));

            return;
        }

        // Le site demandé doit faire partie de ceux auxquels l'utilisateur
        // est réellement affecté : on ne fait jamais confiance à l'identifiant
        // reçu du navigateur.
        $bakery = $context->availableBakeries()->firstWhere('id', $bakeryId);

        abort_if($bakery === null, 403);

        $context->switchTo($bakery);
        $this->redirect(route('dashboard'));
    }

    public function render(): View
    {
        $context = app(BakeryContext::class);

        return view('livewire.sites.site-switcher', [
            'current' => $context->current(),
            'allowsAllSites' => $context->allowsAllSites(),
            'bakeries' => $context->availableBakeries(),
        ]);
    }
}
