<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Sites\BakeryContext;
use App\Domain\Sites\BakeryProfileService;
use App\Http\Requests\UpdateBakeryIdentityRequest;
use App\Models\Bakery;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Paramètres de l'application (cahier §19).
 *
 * La page porte l'identité du site courant : elle s'affiche pour qui a
 * `settings.view`, mais la modification (nom, code, adresse, téléphone,
 * logo) n'est ouverte qu'à qui peut `update` la boulangerie — en pratique le
 * Super-Admin (BakeryPolicy, ADR-033). La persistance est déléguée à
 * App\Domain\Sites\BakeryProfileService.
 */
class SettingsController extends Controller
{
    public function __construct(
        private readonly BakeryProfileService $profiles,
    ) {}

    public function index(BakeryContext $context): View
    {
        return view('settings.index', [
            'bakery' => $context->current(),
        ]);
    }

    public function updateSite(UpdateBakeryIdentityRequest $request, BakeryContext $context): RedirectResponse
    {
        $bakery = $context->current();

        // La Form Request a déjà refusé l'accès sans site courant ; ce
        // garde-fou évite toute écriture hors d'un site identifié.
        if (! $bakery instanceof Bakery) {
            abort(403, 'Aucun site courant : sélectionnez une boulangerie avant de modifier ses informations.');
        }

        $this->profiles->update(
            $bakery,
            $request->validated(),
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        return redirect()
            ->route('settings.index')
            ->with('status', "Les informations de « {$bakery->name} » ont été mises à jour.");
    }
}
