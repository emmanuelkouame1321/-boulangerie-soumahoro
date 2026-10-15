<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Sites\BakeryContext;
use App\Enums\Role;
use App\Enums\StockAlertStatus;
use App\Models\StockAlert;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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

        // Un transfert inter-sites n'utilise pas le scope global par site
        // (il relie deux sites). La résolution de route est donc bornée
        // explicitement aux sites accessibles à l'utilisateur : un lien
        // direct vers un transfert étranger renvoie un 404, comme pour les
        // modèles isolés par `BelongsToBakery`.
        $this->app->make(Router::class)->bind('transfer', static function (string $value): StockTransfer {
            /** @var list<int> $siteIds */
            $siteIds = app(BakeryContext::class)->availableBakeries()->pluck('id')->all();

            return StockTransfer::query()
                ->involvingAny($siteIds)
                ->whereKey($value)
                ->firstOrFail();
        });

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

        // Le défi 2FA se devine en force brute : cinq essais par minute et
        // par compte suffisent largement à un usage légitime.
        RateLimiter::for('two-factor', static function (Request $request): Limit {
            $user = $request->user();

            return Limit::perMinute(5)->by(
                $user !== null ? 'two-factor:'.$user->getAuthIdentifier() : 'two-factor:'.$request->ip()
            );
        });

        // Demande et réinitialisation de mot de passe : limite l'envoi
        // massif de liens et le balayage de jetons.
        RateLimiter::for('password-reset', static fn (Request $request) => Limit::perMinute(6)
            ->by($request->ip()));

        // Un export mobilise la file d'attente : dix demandes par minute
        // et par compte évitent de la saturer depuis un seul poste.
        RateLimiter::for('exports', static function (Request $request): Limit {
            $user = $request->user();

            return Limit::perMinute(10)->by(
                $user !== null ? 'exports:'.$user->getAuthIdentifier() : 'exports:'.$request->ip()
            );
        });

        // Cahier §22 (« notification tableau de bord ») : compteur
        // d'alertes ouvertes du site courant, affiché sur la cloche de la
        // topbar. Le scope par site isole automatiquement les comptages ;
        // sans permission `alert.view`, le compteur reste à zéro.
        View::composer('components.layout.topbar', static function (ViewContract $view): void {
            $user = auth()->user();
            $count = 0;

            if ($user !== null && $user->can('alert.view')) {
                $count = StockAlert::query()
                    ->where('status', StockAlertStatus::Open->value)
                    ->count();
            }

            $view->with('openAlertCount', $count);
        });
    }
}
