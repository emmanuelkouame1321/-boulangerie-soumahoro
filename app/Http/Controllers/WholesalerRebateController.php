<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Customers\WholesalerRebateService;
use App\Domain\Sites\BakeryContext;
use App\Enums\RebateBasis;
use App\Exceptions\RebateAlreadyValidatedException;
use App\Http\Requests\StoreWholesalerRuleRequest;
use App\Http\Requests\UpdateWholesalerRuleRequest;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Wholesaler;
use App\Models\WholesalerRebate;
use App\Models\WholesalerRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Ristournes des grossistes (cahier §13).
 *
 * L'écran administre les paliers (`wholesaler_rules`), lance le calcul
 * d'une ristourne annuelle, puis la valide et enregistre son versement.
 * Le calcul vit dans WholesalerRebateService ; le contrôleur ne fait
 * qu'orchestrer et rendre compte.
 */
class WholesalerRebateController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly WholesalerRebateService $rebates,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', WholesalerRebate::class);

        $year = (int) $request->query('year', (string) now()->year);

        $rebates = WholesalerRebate::query()
            ->with('wholesaler')
            ->forYear($year)
            ->orderByDesc('theoretical_value')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('rebates.index', [
            'rebates' => $rebates,
            'year' => $year,
            'summary' => [
                'theoretical' => (int) WholesalerRebate::query()->forYear($year)->sum('theoretical_value'),
                'validated' => (int) WholesalerRebate::query()->forYear($year)->sum('validated_value'),
                'paid' => (int) WholesalerRebate::query()->forYear($year)->sum('paid_value'),
            ],
            'rules' => WholesalerRule::query()->with('productCategory')->ordered()->get(),
            'bases' => (array) config('boulangerie.rebate_bases'),
            'categories' => ProductCategory::query()->orderBy('position')->pluck('name', 'id')->all(),
            'wholesalerOptions' => Wholesaler::query()->active()->orderBy('name')->pluck('name', 'id')->all(),
            'canManage' => $request->user()?->can('create', WholesalerRebate::class) ?? false,
        ]);
    }

    public function storeRule(StoreWholesalerRuleRequest $request, BakeryContext $context): RedirectResponse
    {
        $this->authorize('create', WholesalerRebate::class);

        $bakery = $context->current() ?? $context->availableBakeries()->first();

        if ($bakery === null) {
            return back()->withInput()->with('error', 'Sélectionnez une boulangerie avant de créer un palier.');
        }

        WholesalerRule::create($this->ruleAttributes($request->validated(), $bakery->getKey()));

        return redirect()
            ->route('rebates.index')
            ->with('status', 'Le palier de ristourne a été enregistré.');
    }

    public function updateRule(UpdateWholesalerRuleRequest $request, WholesalerRule $rule): RedirectResponse
    {
        $this->authorize('update', WholesalerRebate::class);

        $rule->fill($this->ruleAttributes($request->validated(), $rule->bakery_id))->save();

        return redirect()
            ->route('rebates.index')
            ->with('status', 'Le palier de ristourne a été mis à jour.');
    }

    public function destroyRule(WholesalerRule $rule): RedirectResponse
    {
        $this->authorize('update', WholesalerRebate::class);

        // Les paliers ne sont pas supprimés : ils sont désactivés, afin de
        // préserver l'historique des ristournes déjà calculées (§53-11).
        $rule->update(['is_active' => false]);

        return redirect()
            ->route('rebates.index')
            ->with('status', 'Le palier a été désactivé.');
    }

    public function compute(Request $request, BakeryContext $context): RedirectResponse
    {
        $this->authorize('create', WholesalerRebate::class);

        $data = $request->validate([
            'wholesaler_id' => ['required', 'integer', 'exists:wholesalers,id'],
            'period_year' => ['required', 'integer', 'between:2000,2100'],
            'basis' => ['required', 'string', 'in:'.implode(',', RebateBasis::values())],
        ]);

        $bakery = $context->current() ?? $context->availableBakeries()->first();
        $wholesaler = Wholesaler::query()->findOrFail((int) $data['wholesaler_id']);
        $user = $request->user();

        if ($bakery === null) {
            return back()->with('error', 'Sélectionnez une boulangerie avant de calculer une ristourne.');
        }

        try {
            $this->rebates->compute(
                $bakery,
                $wholesaler,
                (int) $data['period_year'],
                RebateBasis::from((string) $data['basis']),
                $user instanceof User ? $user : null,
            );
        } catch (RebateAlreadyValidatedException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('rebates.index', ['year' => $data['period_year']])
            ->with('status', "La ristourne de « {$wholesaler->name} » a été calculée.");
    }

    public function validateRebate(Request $request, WholesalerRebate $rebate): RedirectResponse
    {
        $this->authorize('validate', WholesalerRebate::class);

        $data = $request->validate([
            'validated_value' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        if (! $user instanceof User) {
            return back()->with('error', 'Utilisateur introuvable.');
        }

        try {
            $this->rebates->validate($rebate, $user, $data['validated_value'] ?? null, $data['note'] ?? null);
        } catch (RebateAlreadyValidatedException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'La ristourne a été validée.');
    }

    public function settle(Request $request, WholesalerRebate $rebate): RedirectResponse
    {
        $this->authorize('settle', WholesalerRebate::class);

        $data = $request->validate([
            'paid_value' => ['nullable', 'integer', 'min:0'],
            'settled_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        if (! $user instanceof User) {
            return back()->with('error', 'Utilisateur introuvable.');
        }

        try {
            $this->rebates->settle(
                $rebate,
                $user,
                $data['paid_value'] ?? null,
                Carbon::parse((string) $data['settled_on']),
                $data['note'] ?? null,
            );
        } catch (RebateAlreadyValidatedException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Le versement de la ristourne a été enregistré.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function ruleAttributes(array $data, int $bakeryId): array
    {
        return [
            'bakery_id' => $bakeryId,
            'name' => $data['name'],
            'basis' => $data['basis'],
            'product_category_id' => $data['product_category_id'] ?? null,
            'min_threshold' => $data['min_threshold'],
            'max_threshold' => $data['max_threshold'] ?? null,
            'rate' => $data['rate'],
            'is_active' => (bool) ($data['is_active'] ?? true),
            'position' => (int) ($data['position'] ?? 0),
        ];
    }
}
