<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Recettes / nomenclatures de production (cahier §5, §6 et §20.2).
 *
 * Consultables par les responsables de production. La configuration
 * détaillée des recettes est livrée avec l'administration du catalogue ;
 * cet écran expose les ressources et le rendement théorique.
 */
class RecipeController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Recipe::class);

        $search = trim((string) $request->query('search', ''));

        $recipes = Recipe::query()
            ->with(['product.primaryUnit', 'items.product.primaryUnit', 'items.unit'])
            ->search($search)
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('production.recipes.index', [
            'recipes' => $recipes,
            'search' => $search,
        ]);
    }
}
