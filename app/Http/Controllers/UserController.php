<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Bakery;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

/**
 * Gestion des utilisateurs, de leurs rôles et de leurs sites (cahier §20.4).
 *
 * Contrôleur mince : autorisation par UserPolicy, validation par Form
 * Requests. L'affectation multi-sites passe par la table pivot
 * `bakery_user` (`is_default`).
 */
class UserController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->with(['roles', 'bakeries'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', $this->formData(new User));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        /** @var array{name: string, email: string, password: string, roles?: list<string>, bakeries?: list<int>, default_bakery_id?: int|null} $data */
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $user->syncRoles($data['roles'] ?? []);
        $this->syncBakeries($user, $data['bakeries'] ?? [], $data['default_bakery_id'] ?? null);

        return redirect()
            ->route('settings.users.index')
            ->with('status', "Le compte « {$user->name} » a été créé.");
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', $this->formData($user));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        /** @var array{name: string, email: string, password?: string|null, roles?: list<string>, bakeries?: list<int>, default_bakery_id?: int|null} $data */
        $data = $request->validated();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        $user->syncRoles($data['roles'] ?? []);
        $this->syncBakeries($user, $data['bakeries'] ?? [], $data['default_bakery_id'] ?? null);

        return redirect()
            ->route('settings.users.index')
            ->with('status', "Le compte « {$user->name} » a été mis à jour.");
    }

    /**
     * Données communes aux écrans de création et d'édition.
     *
     * @return array<string, mixed>
     */
    private function formData(User $user): array
    {
        return [
            'user' => $user,
            'roleOptions' => Role::query()->orderBy('name')->pluck('name')->all(),
            'selectedRoles' => $user->exists ? $user->getRoleNames()->all() : [],
            'bakeries' => Bakery::selectable(),
            'selectedBakeries' => $user->exists ? $user->bakeries->pluck('id')->all() : [],
            'defaultBakeryId' => $user->exists
                ? $user->bakeries->firstWhere('pivot.is_default', true)?->getKey()
                : null,
        ];
    }

    /**
     * Affecte l'utilisateur à ses sites et désigne son site par défaut.
     *
     * @param  list<int|string>  $ids
     */
    private function syncBakeries(User $user, array $ids, ?int $defaultId): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($defaultId === null && $ids !== []) {
            $defaultId = $ids[0];
        }

        $assignments = [];

        foreach ($ids as $id) {
            $assignments[$id] = ['is_default' => $id === $defaultId];
        }

        $user->bakeries()->sync($assignments);
    }
}
