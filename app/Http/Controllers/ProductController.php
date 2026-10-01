<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $query = Product::query()
            ->with(['category', 'primaryUnit'])
            ->search($search);

        $typeEnum = ProductType::tryFrom($type);

        if ($typeEnum !== null) {
            $query->ofType($typeEnum);
        }

        if ($category !== '') {
            $query->where('product_category_id', (int) $category);
        }

        $products = $query
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'search' => $search,
            'type' => $type,
            'category' => $category,
            'typeOptions' => $this->typeOptions(),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('products.create', [
            'product' => new Product([
                'type' => ProductType::RawMaterial,
                'alert_mode' => 'stock_threshold',
                'is_active' => true,
            ]),
            'typeOptions' => $this->typeOptions(),
            'unitOptions' => $this->unitOptions(),
            'categoryOptions' => $this->categoryOptions(),
            'alertModeOptions' => $this->alertModeOptions(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->validated());

        return redirect()
            ->route('products.index')
            ->with('status', "Le produit « {$product->name} » a été créé.");
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('products.edit', [
            'product' => $product,
            'typeOptions' => $this->typeOptions(),
            'unitOptions' => $this->unitOptions(),
            'categoryOptions' => $this->categoryOptions(),
            'alertModeOptions' => $this->alertModeOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('products.index')
            ->with('status', "Le produit « {$product->name} » a été mis à jour.");
    }

    /**
     * @return array<string, string>
     */
    private function typeOptions(): array
    {
        $options = [];

        foreach (ProductType::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
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
