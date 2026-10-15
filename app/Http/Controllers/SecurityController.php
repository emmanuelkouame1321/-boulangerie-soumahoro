<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Security\TwoFactorService;
use App\Http\Requests\Security\ConfirmTwoFactorRequest;
use App\Http\Requests\Security\TwoFactorPasswordRequest;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Sécurité du compte : vérification en deux étapes (cahier §31).
 *
 * Écran en libre-service — chaque utilisateur gère sa propre protection —
 * placé sous `/settings/security` sans permission : seuls le mot de passe
 * et le code de l'application d'authentification sont exigés.
 */
class SecurityController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $pendingSecret = $this->twoFactor->pendingSecret($user);

        return view('settings.security.index', [
            'enabled' => $this->twoFactor->enabledFor($user),
            'pendingSecret' => $pendingSecret,
            'formattedSecret' => $pendingSecret === null ? null : Totp::format($pendingSecret),
            'provisioningUri' => $pendingSecret === null ? null : $this->twoFactor->provisioningUri($user, $pendingSecret),
            'recoveryCodesRemaining' => $this->twoFactor->recoveryCodesRemaining($user),
            'recoveryCodeCount' => TwoFactorService::RECOVERY_CODE_COUNT,
        ]);
    }

    /**
     * Démarre l'enrôlement : le secret est posé sur le compte, mais la
     * protection ne s'active qu'après confirmation d'un premier code.
     */
    public function enable(TwoFactorPasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $this->twoFactor->begin($user);

        return redirect()
            ->route('settings.security.index')
            ->with('status', 'Scannez ou saisissez la clé dans votre application d’authentification, puis validez un premier code.');
    }

    public function confirm(ConfirmTwoFactorRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        /** @var array{code: string} $data */
        $data = $request->validated();

        $codes = $this->twoFactor->confirm($user, $data['code']);

        if ($codes === []) {
            return redirect()
                ->route('settings.security.index')
                ->withErrors(['code' => 'Ce code ne correspond pas à la clé affichée. Vérifiez l’horloge de votre téléphone, puis réessayez.']);
        }

        // La possession de l'application vient d'être prouvée : la session
        // courante n'a pas à repasser le défi, et les codes de secours ne
        // sont montrés qu'une fois.
        $request->session()->put(TwoFactorService::SESSION_KEY, $user->getKey());

        return redirect()
            ->route('settings.security.index')
            ->with('status', 'La vérification en deux étapes est active.')
            ->with('recoveryCodes', $codes);
    }

    public function disable(TwoFactorPasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $this->twoFactor->disable($user);
        $request->session()->forget(TwoFactorService::SESSION_KEY);

        return redirect()
            ->route('settings.security.index')
            ->with('status', 'La vérification en deux étapes est désactivée.');
    }

    public function regenerateRecoveryCodes(TwoFactorPasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        if (! $this->twoFactor->enabledFor($user)) {
            return redirect()
                ->route('settings.security.index')
                ->withErrors(['password' => 'Activez d’abord la vérification en deux étapes.']);
        }

        $codes = $this->twoFactor->regenerateRecoveryCodes($user);

        return redirect()
            ->route('settings.security.index')
            ->with('status', 'De nouveaux codes de secours ont été générés : les anciens ne fonctionnent plus.')
            ->with('recoveryCodes', $codes);
    }
}
