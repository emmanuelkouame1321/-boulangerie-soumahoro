<?php

use App\Http\Middleware\ResolveBakeryContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias Spatie Permission — les contrôleurs et routes peuvent utiliser
        // ->middleware('permission:inventory.view').
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Résolution du site courant à chaque requête web : c'est ce qui
        // alimente l'isolation par site (cahier §19.2).
        $middleware->web(append: [
            ResolveBakeryContext::class,
        ]);

        // Le contexte de site doit être résolu AVANT la substitution des
        // modèles (route model binding) : sinon un modèle isolé par site ne
        // se résoudrait pas sur une requête isolée (lien direct, premier
        // appel d'une session), et le scope fail-closed renverrait un 404.
        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            ResolveBakeryContext::class,
        );

        $middleware->redirectGuestsTo(fn (): string => route('login'));
        $middleware->redirectUsersTo('/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
