<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Sites\BakeryProfileService;
use App\Enums\BakeryStatus;
use App\Http\Requests\StoreBakeryRequest;
use App\Http\Requests\UpdateBakeryRequest;
use App\Models\Bakery;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Administration des boulangeries (cahier §19).
 *
 * Contrôleur mince : validation déléguée aux Form Requests, autorisation
 * aux Policies, persistance à BakeryProfileService. Les règles métier de
 * site (identité, logo, isolation, contexte courant) vivent dans
 * App\Domain\Sites.
 */
class BakeryController extends Controller
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly BakeryProfileService $profiles,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Bakery::class);

        $search = trim((string) $request->query('search', ''));

        $bakeries = Bakery::query()
            ->with('manager')
            ->withCount('users')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('settings.bakeries.index', [
            'bakeries' => $bakeries,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Bakery::class);

        return view('settings.bakeries.create', [
            'bakery' => new Bakery(['status' => BakeryStatus::Active]),
            'managers' => $this->managers(),
        ]);
    }

    public function store(StoreBakeryRequest $request): RedirectResponse
    {
        $bakery = $this->profiles->create($request->validated(), $request->file('logo'));

        return redirect()
            ->route('bakeries.index')
            ->with('status', "La boulangerie « {$bakery->name} » a été créée.");
    }

    public function edit(Bakery $bakery): View
    {
        $this->authorize('update', $bakery);

        return view('settings.bakeries.edit', [
            'bakery' => $bakery,
            'managers' => $this->managers(),
        ]);
    }

    public function update(UpdateBakeryRequest $request, Bakery $bakery): RedirectResponse
    {
        $this->profiles->update(
            $bakery,
            $request->validated(),
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        return redirect()
            ->route('bakeries.index')
            ->with('status', "La boulangerie « {$bakery->name} » a été mise à jour.");
    }

    public function destroy(Bakery $bakery): RedirectResponse
    {
        $this->authorize('delete', $bakery);

        abort(403);
    }

    /**
     * Responsables potentiels : les utilisateurs de la base.
     *
     * @return Collection<int, User>
     */
    private function managers()
    {
        return User::query()->orderBy('name')->get(['id', 'name']);
    }
}
