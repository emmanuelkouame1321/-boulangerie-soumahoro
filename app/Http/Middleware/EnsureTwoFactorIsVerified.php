<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Security\TwoFactorService;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Second facteur exigé après la connexion (cahier §31).
 *
 * Un compte qui a activé la 2FA mais n'a pas encore franchi le défi est
 * renvoyé vers l'écran de vérification : toutes les autres pages restent
 * inaccessibles. Seules les routes de sortie (défi, déconnexion) sont
 * laissées ouvertes, sans quoi l'utilisateur serait enfermé.
 *
 * Le repère de session porte l'identifiant du compte vérifié : un changement
 * d'utilisateur dans la même session ne peut donc pas hériter du passage.
 */
class EnsureTwoFactorIsVerified
{
    /** Routes joignables sans avoir franchi le second facteur. */
    private const ALLOWED_ROUTES = [
        'two-factor.challenge',
        'two-factor.challenge.store',
        'logout',
        'password.request',
        'password.email',
    ];

    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $this->twoFactor->enabledFor($user)) {
            return $next($request);
        }

        if ($this->isVerified($request, $user)) {
            return $next($request);
        }

        if ($request->routeIs(self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        return redirect()->guest(route('two-factor.challenge'));
    }

    private function isVerified(Request $request, User $user): bool
    {
        $verified = $request->session()->get(TwoFactorService::SESSION_KEY);

        if ($verified === null) {
            return false;
        }

        return (int) $verified === (int) $user->getKey();
    }
}
