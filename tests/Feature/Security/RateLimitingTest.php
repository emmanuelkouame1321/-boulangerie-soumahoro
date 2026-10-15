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
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Limitation de débit des parcours sensibles (cahier §31).
 *
 * Les limiteurs nommés vivent dans AppServiceProvider : `login`,
 * `two-factor`, `password-reset` et `exports`. Le contrôle porte sur
 * l'effet observable — au-delà de la limite, la requête est refusée en
 * 429 — et non sur les valeurs internes du limiteur.
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        $user->bakeries()->attach(Bakery::factory()->create()->getKey(), ['is_default' => true]);

        return $user;
    }

    public function test_the_two_factor_challenge_is_limited_to_five_attempts_per_minute(): void
    {
        $user = $this->user(Role::SalesManager);
        $service = app(TwoFactorService::class);

        $secret = $service->begin($user);
        $service->confirm($user, Totp::code($secret, time()));

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $validCode = (int) Totp::code($secret, time());
        $wrongCode = str_pad((string) (($validCode + 1) % 1_000_000), 6, '0', STR_PAD_LEFT);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('two-factor.challenge.store'), ['code' => $wrongCode])
                ->assertSessionHasErrors('code');
        }

        // Sixième essai dans la même minute : refusé avant même la
        // validation du code.
        $this->post(route('two-factor.challenge.store'), ['code' => $wrongCode])
            ->assertStatus(429);
    }

    public function test_the_password_reset_request_is_limited(): void
    {
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->post(route('password.email'), ['email' => 'inconnu@soumahoro.test'])
                ->assertStatus(302);
        }

        $this->post(route('password.email'), ['email' => 'inconnu@soumahoro.test'])
            ->assertStatus(429);
    }

    public function test_the_exports_are_limited_per_account(): void
    {
        Storage::fake('local');

        $admin = $this->user(Role::SuperAdmin);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->actingAs($admin)
                ->post(route('exports.store'), ['kind' => 'report:stock', 'format' => 'csv'])
                ->assertRedirect(route('exports.index'));
        }

        $this->actingAs($admin)
            ->post(route('exports.store'), ['kind' => 'report:stock', 'format' => 'csv'])
            ->assertStatus(429);
    }
}
