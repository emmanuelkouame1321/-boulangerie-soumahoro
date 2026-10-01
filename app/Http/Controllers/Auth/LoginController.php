<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Nouvel identifiant de session après élévation de privilèges :
        // protection contre la fixation de session (cahier §31).
        $request->session()->regenerate();

        // Le tableau de bord s'adapte au rôle (Design System §79) ; les
        // tableaux de bord dédiés arrivent en Phase 9.
        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        return app(LogoutController::class)($request);
    }
}
