<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Enums\RecipeDeductionMode;
use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Recettes / nomenclatures de production (cahier §5, §6 et §20.2).
 *
 * Consultables par les responsables de production, créées et modifiées par
 * les utilisateurs disposant de `recipe.manage`. La configuration détaillée
 * (produit fini, rendement par lot, durée, pertes autorisées et ressources
 * avec leur mode de déduction) est saisie sur l'écran de création et
 * d'édition. Une recette existante peut servir de modèle via `?from={id}`.
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
            'canManage' => $request->user()?->can('create', Recipe::class) ?? false,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Recipe::class);

        $fromId = (int) $request->query('from', 0);

        $source = $fromId > 0
            ? Recipe::query()->with('items')->find($fromId)
            : null;

        return view('production.recipes.create', $this->formData(
            $this->template($source),
            $source,
        ));
    }

    public function store(StoreRecipeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $recipe = DB::transaction(function () use ($data): Recipe {
            $recipe = Recipe::query()->create([
                'product_id' => (int) $data['product_id'],
                'name' => $data['name'],
                'reference' => $data['reference'] ?? null,
                'yield_quantity' => $data['yield_quantity'],
                'production_time_minutes' => $data['production_time_minutes'] ?? null,
                'shelf_life_days' => $data['shelf_life_days'] ?? null,
                'allowed_loss_percent' => $data['allowed_loss_percent'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'note' => $data['note'] ?? null,
            ]);

            $this->syncItems($recipe, $data['items']);

            return $recipe;
        });

        return redirect()
            ->route('production.recipes.index')
            ->with('status', "La recette « {$recipe->name} » a été créée.");
    }

    public function edit(Recipe $recipe): View
    {
        $this->authorize('update', $recipe);

        $recipe->load('items');

        return view('production.recipes.edit', $this->formData($recipe, null));
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($recipe, $data): void {
            $recipe->update([
                'product_id' => (int) $data['product_id'],
                'name' => $data['name'],
                'reference' => $data['reference'] ?? null,
                'yield_quantity' => $data['yield_quantity'],
                'production_time_minutes' => $data['production_time_minutes'] ?? null,
                'shelf_life_days' => $data['shelf_life_days'] ?? null,
                'allowed_loss_percent' => $data['allowed_loss_percent'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'note' => $data['note'] ?? null,
            ]);

            // La nomenclature est remplacée en bloc : positions reindexées.
            $recipe->items()->delete();
            $this->syncItems($recipe, $data['items']);
        });

        return redirect()
            ->route('production.recipes.index')
            ->with('status', "La recette « {$recipe->name} » a été mise à jour.");
    }

    /**
     * Données communes aux écrans de création et d'édition.
     *
     * @return array<string, mixed>
     */
    private function formData(Recipe $recipe, ?Recipe $source): array
    {
        return [
            'recipe' => $recipe,
            'sourceRecipe' => $source,
            'itemRows' => $this->itemRows($source ?? $recipe),
            'productOptions' => $this->finishedProductOptions(),
            'itemProductOptions' => $this->resourceProductOptions(),
            'unitOptions' => $this->unitOptions(),
            'modeOptions' => $this->deductionModeOptions(),
        ];
    }

    /**
     * Remplace les ressources de la recette à partir des lignes validées.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(Recipe $recipe, array $items): void
    {
        foreach (array_values($items) as $position => $item) {
            $recipe->items()->create([
                'product_id' => (int) $item['product_id'],
                'unit_id' => $item['unit_id'] ?? null,
                'quantity' => $item['quantity'],
                'deduction_mode' => $item['deduction_mode'],
                'unit_cost' => $item['unit_cost'] ?? null,
                'position' => $position,
            ]);
        }
    }

    /**
     * Modèle vierge, ou pré-rempli depuis la recette source (duplication).
     * La référence est toujours vierge : elle doit rester unique.
     */
    private function template(?Recipe $source): Recipe
    {
        if ($source === null) {
            return new Recipe([
                'yield_quantity' => 1,
                'is_active' => true,
            ]);
        }

        return new Recipe([
            'product_id' => $source->product_id,
            'name' => $source->name,
            'reference' => null,
            'yield_quantity' => $source->yield_quantity,
            'production_time_minutes' => $source->production_time_minutes,
            'shelf_life_days' => $source->shelf_life_days,
            'allowed_loss_percent' => $source->allowed_loss_percent,
            'is_active' => $source->is_active,
            'note' => $source->note,
        ]);
    }

    /**
     * Lignes de ressources initiales du formulaire.
     *
     * @return list<array{product_id: string, unit_id: string, quantity: string, deduction_mode: string, unit_cost: string}>
     */
    private function itemRows(Recipe $recipe): array
    {
        if ($recipe->items->isNotEmpty()) {
            /** @var list<array{product_id: string, unit_id: string, quantity: string, deduction_mode: string, unit_cost: string}> $rows */
            $rows = $recipe->items
                ->map(static fn (RecipeItem $item): array => [
                    'product_id' => (string) $item->product_id,
                    'unit_id' => $item->unit_id !== null ? (string) $item->unit_id : '',
                    'quantity' => (string) $item->quantity,
                    'deduction_mode' => $item->deduction_mode->value,
                    'unit_cost' => $item->unit_cost !== null ? (string) $item->unit_cost : '',
                ])
                ->values()
                ->all();

            return $rows;
        }

        return [[
            'product_id' => '',
            'unit_id' => '',
            'quantity' => '1',
            'deduction_mode' => RecipeDeductionMode::Quantity->value,
            'unit_cost' => '',
        ]];
    }

    /**
     * @return array<int, string>
     */
    private function finishedProductOptions(): array
    {
        return Product::query()
            ->active()
            ->ofType(ProductType::FinishedGood)
            ->orderBy('name')
            ->get()
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function resourceProductOptions(): array
    {
        return Product::query()
            ->active()
            ->where('type', '!=', ProductType::FinishedGood->value)
            ->orderBy('name')
            ->get()
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function unitOptions(): array
    {
        return Unit::query()
            ->active()
            ->ordered()
            ->get()
            ->pluck('label', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function deductionModeOptions(): array
    {
        $options = [];

        foreach (RecipeDeductionMode::cases() as $mode) {
            $options[$mode->value] = $mode->label();
        }

        return $options;
    }
}
