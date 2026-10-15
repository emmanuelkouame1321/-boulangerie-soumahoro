<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Catalogue des produits et matières premières (cahier §4).
 *
 * Contrôleur mince : autorisation par ProductPolicy, validation par Form
 * Requests, persistance par Eloquent.
 */
class ProductController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', '');
        $category = (string) $request->query('category', '');
        $group = (string) $request->query('group', 'products');

        if (! in_array($group, ProductType::groups(), true)) {
            $group = 'products';
        }

        // Le catalogue est scindé en deux familles (cahier §4.1) : les
        // produits (produit fini) et les matières premières (tout le reste).
        $query = Product::query()
            ->with(['category', 'primaryUnit'])
            ->search($search)
            ->whereIn('type', $this->groupTypeValues($group));

        $typeEnum = ProductType::tryFrom($type);

        // Le filtre « type » n'affine qu'à l'intérieur du groupe courant.
        if ($typeEnum !== null && $typeEnum->group() === $group) {
            $query->ofType($typeEnum);
        }

        if ($category !== '') {
            $query->where('product_category_id', (int) $category);
        }

        $products = $query
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        /** @var array<string, string> $groupOptions */
        $groupOptions = config('boulangerie.product_groups', []);

        return view('products.index', [
            'products' => $products,
            'search' => $search,
            'type' => $type,
            'category' => $category,
            'group' => $group,
            'groupOptions' => $groupOptions,
            'groupCounts' => $this->groupCounts($search, $category),
            'typeOptions' => $this->typeOptionsForGroup($group),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    /**
     * @return list<string>
     */
    private function groupTypeValues(string $group): array
    {
        return array_map(
            static fn (ProductType $type): string => $type->value,
            ProductType::forGroup($group),
        );
    }

    /**
     * Nombre de produits par famille, en respectant la recherche et la
     * catégorie en cours — mais pas le type ni le groupe, pour comparer.
     *
     * @return array<string, int>
     */
    private function groupCounts(string $search, string $category): array
    {
        $counts = [];

        foreach (ProductType::groups() as $group) {
            $counts[$group] = Product::query()
                ->search($search)
                ->whereIn('type', $this->groupTypeValues($group))
                ->when($category !== '', fn ($query) => $query->where('product_category_id', (int) $category))
                ->count();
        }

        return $counts;
    }

    /**
     * @return array<string, string>
     */
    private function typeOptionsForGroup(string $group): array
    {
        $options = [];

        foreach (ProductType::forGroup($group) as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Product::class);

        // La famille choisie (ex. modale du catalogue) fixe le type par défaut.
        $group = (string) $request->query('group', ProductType::FinishedGood->group());

        if (! in_array($group, ProductType::groups(), true)) {
            $group = ProductType::FinishedGood->group();
        }

        $type = $group === 'materials' ? ProductType::RawMaterial : ProductType::FinishedGood;

        return view('products.create', [
            'product' => new Product([
                'type' => $type,
                'alert_mode' => 'stock_threshold',
                'is_active' => true,
            ]),
            'groupOptions' => $this->groupOptions(),
            'typesByGroup' => $this->typesByGroup(),
            'unitOptions' => $this->unitOptions(),
            'categoryOptions' => $this->categoryOptions(),
            'alertModeOptions' => $this->alertModeOptions(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // La photo est stockée avant la ligne : en cas d'échec, un fichier
        // orphelin reste sans effet visible (aucun lien ne pointe dessus).
        $validated['photo_path'] = $this->storePhoto($request->file('photo'));

        $product = Product::create($validated);

        // On revient sur la famille du produit créé pour qu'il soit visible.
        return redirect()
            ->route('products.index', ['group' => $product->type->group()])
            ->with('status', "Le produit « {$product->name} » a été créé.");
    }

    /**
     * Suggère un code produit (SKU) dérivé du nom, utilisé par le bouton
     * « Générer » du formulaire (cahier §4.2) : préfixe de famille + nom
     * en majuscules (ex. `PF-BAGUETTE`, `MP-FARINE-50-KG`). Le code proposé
     * reste vérifié à l'enregistrement (unicité).
     */
    public function generateSku(Request $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(ProductType::class)],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $type = ProductType::from((string) $validated['type']);

        return response()->json(['sku' => $this->suggestSku($type, (string) $validated['name'])]);
    }

    /**
     * Code proposé : préfixe de famille suivi du nom normalisé (accents
     * translittérés, espaces en tirets, majuscules). Un suffixe numérique
     * n'est ajouté qu'en cas de collision (`PF-BAGUETTE-2`).
     */
    private function suggestSku(ProductType $type, string $name): string
    {
        $slug = Str::upper(Str::slug($name));

        if ($slug === '') {
            $slug = 'ARTICLE';
        }

        // Marge pour le préfixe, le tiret et un éventuel suffixe « -999 ».
        $base = $type->skuPrefix().'-'.substr($slug, 0, 54);

        if (! Product::query()->where('sku', $base)->exists()) {
            return $base;
        }

        for ($suffix = 2; $suffix <= 999; $suffix++) {
            $candidate = $base.'-'.$suffix;

            if (! Product::query()->where('sku', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Extrêmement improbable : on garantit tout de même l'unicité.
        return $base.'-'.Str::upper(Str::random(4));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('products.edit', [
            'product' => $product,
            'groupOptions' => $this->groupOptions(),
            'typesByGroup' => $this->typesByGroup(),
            'unitOptions' => $this->unitOptions(),
            'categoryOptions' => $this->categoryOptions(),
            'alertModeOptions' => $this->alertModeOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();
        $previousPath = $product->photo_path;

        unset($validated['photo'], $validated['remove_photo']);

        // Une nouvelle image remplace l'ancienne ; à défaut, la case
        // « Supprimer la photo » efface le fichier existant.
        $newPath = $this->storePhoto($request->file('photo'));

        if ($newPath !== null) {
            $validated['photo_path'] = $newPath;
        } elseif ($request->boolean('remove_photo')) {
            $validated['photo_path'] = null;
        }

        $product->update($validated);

        // Le fichier n'est retiré qu'après la mise à jour réussie.
        if (array_key_exists('photo_path', $validated) && $validated['photo_path'] !== $previousPath) {
            $this->forgetPhoto($previousPath);
        }

        return redirect()
            ->route('products.index')
            ->with('status', "Le produit « {$product->name} » a été mis à jour.");
    }

    /**
     * Dépose une photo sur le disque public et renvoie son chemin relatif
     * (`products/<hash>.<ext>`), ou null si aucune image n'a été envoyée.
     */
    private function storePhoto(?UploadedFile $file): ?string
    {
        if ($file === null) {
            return null;
        }

        return $file->store('products', 'public');
    }

    private function forgetPhoto(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return array<string, string>
     */
    private function groupOptions(): array
    {
        /** @var array<string, string> $options */
        $options = config('boulangerie.product_groups', []);

        return $options;
    }

    /**
     * Types proposés par famille, pour le sélecteur « nature » du
     * formulaire (la famille « produits » n'a qu'un type : produit fini).
     *
     * @return array<string, array<string, string>>
     */
    private function typesByGroup(): array
    {
        $result = [];

        foreach (ProductType::groups() as $group) {
            $types = [];

            foreach (ProductType::forGroup($group) as $type) {
                $types[$type->value] = $type->label();
            }

            $result[$group] = $types;
        }

        return $result;
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
     * @return array<int, string>
     */
    private function categoryOptions(): array
    {
        return ProductCategory::query()
            ->active()
            ->ordered()
            ->get()
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function alertModeOptions(): array
    {
        /** @var array<string, string> $modes */
        $modes = config('boulangerie.alert_modes', []);

        return $modes;
    }
}
