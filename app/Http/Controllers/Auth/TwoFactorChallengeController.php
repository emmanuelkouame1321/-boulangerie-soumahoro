<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Security\TwoFactorService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Vérification en deux étapes à la connexion (cahier §31).
 *
 * L'utilisateur est authentifié par son mot de passe, mais aucune page
 * applicative ne lui est ouverte avant d'avoir saisi un code valide
 * (middleware EnsureTwoFactorIsVerified).
 */
class TwoFactorChallengeController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        if ($this->alreadyVerified($request, $user)) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.two-factor-challenge');
    }

    public function store(TwoFactorChallengeRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        if (! $this->twoFactor->enabledFor($user)) {
            return redirect()->intended(route('dashboard'));
        }

        /** @var array{code: string} $data */
        $data = $request->validated();

        if (! $this->twoFactor->verify($user, $data['code'])) {
            throw ValidationException::withMessages([
                'code' => 'Ce code est invalide ou expiré. Vérifiez l’horloge de votre téléphone ou utilisez un code de secours.',
            ]);
        }

        // Nouvel identifiant de session après élévation de privilèges
        // (cahier §31) : le repère de vérification suit la session migrée.
        $request->session()->put(TwoFactorService::SESSION_KEY, $user->getKey());
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function alreadyVerified(Request $request, User $user): bool
    {
        if (! $this->twoFactor->enabledFor($user)) {
            return true;
        }

        $verified = $request->session()->get(TwoFactorService::SESSION_KEY);

        return $verified !== null && (int) $verified === (int) $user->getKey();
    }
}
