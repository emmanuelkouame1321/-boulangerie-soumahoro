<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Domain\Security\TwoFactorService;
use App\Enums\Role;
use App\Models\Bakery;
use App\Models\User;
use App\Support\Totp;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Vérification en deux étapes optionnelle (cahier §31).
 *
 * Un compte qui l'active ne peut plus atteindre aucune page applicative
 * avec le seul mot de passe : le défi passe avant, et un code de secours
 * consommé ne se rejoue pas.
 */
class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $bakery = Bakery::factory()->create();

        $this->user = User::factory()->create(['name' => 'Responsable vente']);
        $this->user->assignRole(Role::SalesManager->value);
        $this->user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);
    }

    /**
     * Active la 2FA par le service, sans passer par l'écran, et retourne
     * les codes de secours en clair.
     *
     * @return list<string>
     */
    private function activateTwoFactor(): array
    {
        $service = app(TwoFactorService::class);

        $secret = $service->begin($this->user);

        return $service->confirm($this->user, Totp::code($secret, time()));
    }

    private function login(): void
    {
        $this->post(route('login'), [
            'email' => $this->user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
    }

    private function challenge(string $code): void
    {
        $this->post(route('two-factor.challenge.store'), ['code' => $code])
            ->assertRedirect();
    }

    private function secret(): string
    {
        $secret = $this->user->refresh()->getAttribute('two_factor_secret');

        if (! is_string($secret) || $secret === '') {
            $this->fail('Le compte testé n’a pas de secret TOTP.');
        }

        return $secret;
    }

    private function currentCode(): string
    {
        return Totp::code($this->secret(), time());
    }

    /**
     * Un code à six chiffres faux mais déterministe (le code juste décalé
     * d'une unité).
     */
    private function wrongCode(): string
    {
        return str_pad((string) (((int) $this->currentCode() + 1) % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    public function test_an_account_can_activate_two_factor_and_receives_recovery_codes(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.security.enable'), ['password' => 'password'])
            ->assertRedirect(route('settings.security.index'));

        $secret = $this->secret();

        $this->assertNull($this->user->refresh()->two_factor_confirmed_at);

        // La clé est affichée pour être saisie dans l'application.
        $this->actingAs($this->user)
            ->get(route('settings.security.index'))
            ->assertOk()
            ->assertSee(Totp::format($secret));

        $this->actingAs($this->user)
            ->post(route('settings.security.confirm'), ['code' => Totp::code($secret, time())])
            ->assertRedirect(route('settings.security.index'))
            ->assertSessionHas(
                'recoveryCodes',
                static fn (mixed $codes): bool => is_array($codes) && count($codes) === TwoFactorService::RECOVERY_CODE_COUNT
            );

        $this->assertNotNull($this->user->refresh()->two_factor_confirmed_at);
        $this->assertSame(
            TwoFactorService::RECOVERY_CODE_COUNT,
            app(TwoFactorService::class)->recoveryCodesRemaining($this->user)
        );

        $this->actingAs($this->user)
            ->get(route('settings.security.index'))
            ->assertOk()
            ->assertSee('Activée');
    }

    public function test_activation_keeps_the_current_session_open(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.security.enable'), ['password' => 'password']);

        $this->actingAs($this->user)
            ->post(route('settings.security.confirm'), ['code' => $this->currentCode()])
            ->assertRedirect(route('settings.security.index'));

        // La possession de l'application vient d'être prouvée : pas de
        // défi à repasser dans la foulée.
        $this->actingAs($this->user)->get(route('dashboard'))->assertOk();
    }

    public function test_activation_requires_the_current_password(): void
    {
        $this->actingAs($this->user)
            ->post(route('settings.security.enable'), ['password' => 'mauvais-mot-de-passe'])
            ->assertSessionHasErrors('password');

        $this->assertNull($this->user->refresh()->two_factor_secret);
    }

    public function test_a_login_with_two_factor_asks_for_the_code(): void
    {
        $this->activateTwoFactor();

        $this->login();

        // Aucune page applicative avant le second facteur.
        $this->get(route('dashboard'))->assertRedirect(route('two-factor.challenge'));

        $this->get(route('two-factor.challenge'))
            ->assertOk()
            ->assertSee('Vérification en deux étapes');

        // Le défi franchi, la session revient sur la page demandée.
        $this->get(route('sales.index'))->assertRedirect(route('two-factor.challenge'));
        $this->post(route('two-factor.challenge.store'), ['code' => $this->currentCode()])
            ->assertRedirect(route('sales.index'));

        $this->get(route('sales.index'))->assertOk();
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_an_invalid_code_leaves_the_session_blocked(): void
    {
        $this->activateTwoFactor();
        $this->login();

        $this->post(route('two-factor.challenge.store'), ['code' => $this->wrongCode()])
            ->assertSessionHasErrors('code');

        $this->get(route('dashboard'))->assertRedirect(route('two-factor.challenge'));
    }

    public function test_a_recovery_code_is_consumed_when_used(): void
    {
        $codes = $this->activateTwoFactor();
        $this->login();
        $this->challenge($codes[0]);

        $this->get(route('dashboard'))->assertOk();

        // Nouvelle session : le code déjà utilisé ne fonctionne plus.
        Auth::logout();
        $this->flushSession();
        $this->login();

        $this->post(route('two-factor.challenge.store'), ['code' => $codes[0]])
            ->assertSessionHasErrors('code');

        // Le suivant, lui, est toujours valable.
        $this->challenge($codes[1]);

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_two_factor_is_disabled_only_with_the_password(): void
    {
        $this->activateTwoFactor();
        $this->login();
        $this->challenge($this->currentCode());

        $this->delete(route('settings.security.disable'), ['password' => 'mauvais'])
            ->assertSessionHasErrors('password');

        $this->assertNotNull($this->user->refresh()->two_factor_confirmed_at);

        $this->delete(route('settings.security.disable'), ['password' => 'password'])
            ->assertRedirect(route('settings.security.index'));

        $this->assertNull($this->user->refresh()->two_factor_secret);

        // La connexion repasse en une seule étape.
        Auth::logout();
        $this->flushSession();
        $this->login();

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_the_recovery_codes_can_be_regenerated(): void
    {
        $codes = $this->activateTwoFactor();
        $this->login();
        $this->challenge($this->currentCode());

        $this->post(route('settings.security.recovery-codes'), ['password' => 'password'])
            ->assertRedirect(route('settings.security.index'))
            ->assertSessionHas('recoveryCodes');

        $this->assertSame(
            TwoFactorService::RECOVERY_CODE_COUNT,
            app(TwoFactorService::class)->recoveryCodesRemaining($this->user->refresh())
        );

        // Les anciens codes ne fonctionnent plus.
        Auth::logout();
        $this->flushSession();
        $this->login();

        $this->post(route('two-factor.challenge.store'), ['code' => $codes[0]])
            ->assertSessionHasErrors('code');
    }

    public function test_an_account_without_two_factor_reaches_the_dashboard_directly(): void
    {
        $this->login();

        $this->get(route('dashboard'))->assertOk();
    }
}
