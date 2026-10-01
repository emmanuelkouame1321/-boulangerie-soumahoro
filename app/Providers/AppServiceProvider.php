<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Sites\BakeryContext;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Le contexte de site est un singleton : le middleware le renseigne
        // et tous les consommateurs (scope global, composants, services)
        // doivent lire la même instance pendant la requête.
        $this->app->singleton(BakeryContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard(false);

        // Cahier §31 : les permissions sont vérifiées côté serveur.
        // Le Super-Admin dispose d'un accès complet sans énumérer chaque
        // permission (équivalent du wildcard '*' de config/boulangerie.php).
        Gate::before(static function (User $user, string $ability): ?bool {
            return $user->hasRole(Role::SuperAdmin->value) ? true : null;
        });

        // Cahier §31 : protection contre le balayage de comptes depuis une
        // même adresse IP. La limitation par compte est dans LoginRequest.
        RateLimiter::for('login', static fn (Request $request) => Limit::perMinute(20)
            ->by($request->ip()));
    }
}
