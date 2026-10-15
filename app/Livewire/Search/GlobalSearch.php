<?php

declare(strict_types=1);

namespace App\Livewire\Search;

use App\Domain\Search\GlobalSearchService;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Palette de recherche globale (topbar, Design System §10).
 *
 * Le composant ne porte que le **panneau** : le déclencheur vit dans la
 * topbar et ouvre la palette via l'événement `open-global-search`, comme le
 * bouton burger ouvre la navigation latérale. Le panneau est monté à la
 * racine de la coquille applicative — et non dans l'en-tête — pour que son
 * `z-index` échappe au contexte d'empilement de la topbar collante.
 *
 * Les résultats sont calculés côté serveur à chaque frappe
 * (`wire:model.live.debounce`) : permissions et isolation par site sont
 * appliquées par `GlobalSearchService`. Raccourcis : Ctrl/Cmd + K pour
 * ouvrir, Échap pour fermer.
 */
class GlobalSearch extends Component
{
    /**
     * Terme saisi. Vidé à la fermeture pour ne pas rouvrir la palette sur
     * une recherche précédente devenue obsolète.
     */
    public string $term = '';

    public function clear(): void
    {
        $this->term = '';
    }

    public function render(): View
    {
        $user = auth()->user();
        $term = trim($this->term);

        $groups = $user instanceof User
            ? app(GlobalSearchService::class)->search($term, $user)
            : [];

        return view('livewire.search.global-search', [
            'term' => $term,
            'groups' => $groups,
            'minimumLength' => GlobalSearchService::MIN_TERM_LENGTH,
        ]);
    }
}
