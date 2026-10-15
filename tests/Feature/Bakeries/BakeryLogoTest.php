<?php

declare(strict_types=1);

namespace Tests\Feature\Bakeries;

use App\Enums\Role;
use App\Models\Bakery;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Logo du site (cahier §19.1, ADR-032) : téléversement, remplacement,
 * suppression, affichage sur les écrans et favicon de l'onglet.
 *
 * Le logo vit sur le disque `public` (`bakeries/<hash>.<ext>`) ; la colonne
 * `bakeries.logo_path` est nullable et seule elle est persistée.
 */
class BakeryLogoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PNG 1×1 valide : un fichier réel permet de valider la règle `image`
     * sans dépendre de l'extension GD (non requise par getimagesize()).
     */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        return $user;
    }

    private function png(string $name = 'logo.png'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'logo').'.png';
        file_put_contents($path, (string) base64_decode(self::PNG, true));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function bakeryWithLogo(): Bakery
    {
        $path = 'bakeries/existant.png';
        Storage::disk('public')->put($path, (string) base64_decode(self::PNG, true));

        return Bakery::factory()->create([
            'name' => 'Boulangerie Soumahoro',
            'logo_path' => $path,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Bakery $bakery, array $overrides = []): array
    {
        return array_merge([
            'code' => $bakery->code,
            'name' => $bakery->name,
            'address' => $bakery->address,
            'phone' => $bakery->phone,
            'status' => $bakery->status->value,
            'manager_id' => null,
            'notes' => null,
        ], $overrides);
    }

    public function test_a_logo_can_be_uploaded_when_creating_a_bakery(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superAdmin())
            ->post(route('bakeries.store'), [
                'code' => 'BLG-ABJ',
                'name' => 'Boulangerie Soumahoro',
                'status' => 'active',
                'logo' => $this->png(),
            ])
            ->assertRedirect(route('bakeries.index'));

        $bakery = Bakery::query()->firstOrFail();

        $this->assertNotNull($bakery->logo_path);
        $this->assertStringStartsWith('bakeries/', (string) $bakery->logo_path);
        Storage::disk('public')->assertExists((string) $bakery->logo_path);
    }

    public function test_a_bakery_can_be_saved_without_a_logo(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superAdmin())
            ->post(route('bakeries.store'), [
                'code' => 'BLG-ABJ',
                'name' => 'Boulangerie Soumahoro',
                'status' => 'active',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull(Bakery::query()->firstOrFail()->logo_path);
    }

    public function test_a_file_that_is_not_an_image_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superAdmin())
            ->post(route('bakeries.store'), [
                'code' => 'BLG-ABJ',
                'name' => 'Boulangerie Soumahoro',
                'status' => 'active',
                'logo' => UploadedFile::fake()->create('fiche.pdf', 120, 'application/pdf'),
            ])
            ->assertSessionHasErrors('logo');

        $this->assertDatabaseCount('bakeries', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('bakeries'));
    }

    public function test_the_name_can_be_changed_from_the_admin_screen(): void
    {
        $bakery = Bakery::factory()->create(['name' => 'Ancien nom']);

        $this->actingAs($this->superAdmin())
            ->put(route('bakeries.update', $bakery), $this->payload($bakery, [
                'name' => 'Boulangerie Soumahoro',
            ]))
            ->assertRedirect(route('bakeries.index'));

        $this->assertSame('Boulangerie Soumahoro', $bakery->refresh()->name);
    }

    public function test_a_new_logo_replaces_the_previous_file(): void
    {
        Storage::fake('public');
        $bakery = $this->bakeryWithLogo();
        $previous = (string) $bakery->logo_path;

        $this->actingAs($this->superAdmin())
            ->put(route('bakeries.update', $bakery), $this->payload($bakery, [
                'logo' => $this->png('nouveau.png'),
            ]))
            ->assertRedirect(route('bakeries.index'));

        $bakery->refresh();

        $this->assertNotNull($bakery->logo_path);
        $this->assertNotSame($previous, $bakery->logo_path);
        Storage::disk('public')->assertExists((string) $bakery->logo_path);
        Storage::disk('public')->assertMissing($previous);
    }

    public function test_the_logo_can_be_removed(): void
    {
        Storage::fake('public');
        $bakery = $this->bakeryWithLogo();
        $previous = (string) $bakery->logo_path;

        $this->actingAs($this->superAdmin())
            ->put(route('bakeries.update', $bakery), $this->payload($bakery, [
                'remove_logo' => '1',
            ]))
            ->assertRedirect(route('bakeries.index'));

        $this->assertNull($bakery->refresh()->logo_path);
        Storage::disk('public')->assertMissing($previous);
    }

    public function test_an_update_without_a_file_keeps_the_existing_logo(): void
    {
        Storage::fake('public');
        $bakery = $this->bakeryWithLogo();
        $previous = (string) $bakery->logo_path;

        $this->actingAs($this->superAdmin())
            ->put(route('bakeries.update', $bakery), $this->payload($bakery, [
                'name' => 'Nouveau nom',
            ]))
            ->assertRedirect(route('bakeries.index'));

        $bakery->refresh();

        $this->assertSame('Nouveau nom', $bakery->name);
        $this->assertSame($previous, $bakery->logo_path);
        Storage::disk('public')->assertExists($previous);
    }

    public function test_the_edit_screen_offers_to_remove_an_existing_logo(): void
    {
        Storage::fake('public');
        $bakery = $this->bakeryWithLogo();

        $this->actingAs($this->superAdmin())
            ->get(route('bakeries.edit', $bakery))
            ->assertOk()
            ->assertSee('multipart/form-data', false)
            ->assertSee((string) $bakery->logoUrl(), false)
            ->assertSee('Supprimer le logo');
    }

    public function test_the_edit_screen_hides_the_removal_option_without_a_logo(): void
    {
        $bakery = Bakery::factory()->create();

        $this->actingAs($this->superAdmin())
            ->get(route('bakeries.edit', $bakery))
            ->assertOk()
            ->assertDontSee('Supprimer le logo');
    }

    public function test_the_admin_list_shows_the_logo(): void
    {
        Storage::fake('public');
        $bakery = $this->bakeryWithLogo();

        $this->actingAs($this->superAdmin())
            ->get(route('bakeries.index'))
            ->assertOk()
            ->assertSee((string) $bakery->logoUrl(), false);
    }

    public function test_the_dashboard_shows_the_site_identity_and_links_to_the_settings(): void
    {
        Storage::fake('public');
        $bakery = $this->bakeryWithLogo();
        $user = $this->superAdmin();
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);
        $this->actingAs($user)->withSession(['bakery_id' => $bakery->getKey()]);

        // La modification du site se fait dans les paramètres (ADR-033).
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Boulangerie Soumahoro')
            ->assertSee((string) $bakery->logoUrl(), false)
            ->assertSee('Paramètres du site');
    }

    public function test_the_dashboard_hides_the_identity_block_from_a_standard_user(): void
    {
        Storage::fake('public');
        $bakery = $this->bakeryWithLogo();
        $user = User::factory()->create();
        $user->assignRole(Role::SalesManager->value);
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            // Le nom du site reste affiché, mais l'accès aux paramètres n'est
            // pas proposé (permission serveur).
            ->assertSee('Boulangerie Soumahoro')
            ->assertDontSee('Paramètres du site');
    }

    public function test_the_favicon_is_the_logo_of_the_current_site(): void
    {
        Storage::fake('public');
        $bakery = $this->bakeryWithLogo();
        $user = User::factory()->create();
        $user->assignRole(Role::SalesManager->value);
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<link rel="icon" href="'.$bakery->logoUrl().'">', false);
    }

    public function test_the_favicon_falls_back_to_the_shipped_file(): void
    {
        Bakery::factory()->create(['name' => 'Sans logo']);
        $user = User::factory()->create();
        $user->assignRole(Role::SalesManager->value);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<link rel="icon" href="'.asset('favicon.ico').'">', false);
    }

    public function test_a_consolidated_super_admin_still_gets_a_site_logo(): void
    {
        Storage::fake('public');
        $bakery = $this->bakeryWithLogo();

        $this->actingAs($this->superAdmin())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<link rel="icon" href="'.$bakery->logoUrl().'">', false);
    }

    public function test_the_login_screen_uses_the_shipped_favicon(): void
    {
        Bakery::factory()->create(['name' => 'Sans logo']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<link rel="icon" href="'.asset('favicon.ico').'">', false);
    }
}
