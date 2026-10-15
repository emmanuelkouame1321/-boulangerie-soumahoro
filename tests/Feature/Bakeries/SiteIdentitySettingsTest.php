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
 * Identité du site depuis les paramètres (cahier §19.2, ADR-033).
 *
 * La page Paramètres porte la fiche du site courant ; la modification est
 * réservée au Super-Admin et le site visé vient du contexte de site, jamais
 * du formulaire.
 */
class SiteIdentitySettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PNG 1×1 valide : un fichier réel permet de valider la règle `image`
     * sans dépendre de l'extension GD.
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

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    private function png(string $name = 'logo.png'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'logo').'.png';
        file_put_contents($path, (string) base64_decode(self::PNG, true));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    private function bakery(array $attributes = []): Bakery
    {
        return Bakery::factory()->create(array_merge([
            'name' => 'Boulangerie Soumahoro',
            'code' => 'BLG-ABJ',
            'address' => 'Rue du Marché',
            'phone' => '+225 07 00 00 00',
        ], $attributes));
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
        ], $overrides);
    }

    public function test_guests_cannot_reach_the_settings_screen(): void
    {
        $this->get('/settings')->assertRedirect('/login');
        $this->put('/settings/site', ['name' => 'X', 'code' => 'X'])->assertRedirect('/login');
    }

    public function test_a_standard_role_cannot_reach_the_settings_screen(): void
    {
        $this->actingAs($this->userWithRole(Role::SalesManager))
            ->get('/settings')
            ->assertForbidden();
    }

    public function test_the_settings_screen_shows_the_current_site_information(): void
    {
        Storage::fake('public');

        $path = 'bakeries/existant.png';
        Storage::disk('public')->put($path, (string) base64_decode(self::PNG, true));

        $bakery = $this->bakery(['logo_path' => $path]);
        $user = $this->superAdmin();
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);

        $this->actingAs($user)
            ->withSession(['bakery_id' => $bakery->getKey()])
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Informations du site')
            ->assertSee('Boulangerie Soumahoro')
            ->assertSee('BLG-ABJ')
            ->assertSee('Rue du Marché')
            ->assertSee('+225 07 00 00 00')
            // Le logo est bien rendu depuis le disque public (ADR-033).
            ->assertSee((string) $bakery->logoUrl(), false)
            ->assertSee(route('settings.site.update'), false)
            ->assertSee('Enregistrer les informations');
    }

    public function test_the_settings_screen_asks_for_a_site_when_none_is_open(): void
    {
        $this->bakery();

        $this->actingAs($this->superAdmin())
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Aucun site sélectionné')
            ->assertDontSee(route('settings.site.update'), false);
    }

    public function test_the_settings_screen_is_read_only_for_a_user_without_the_permission(): void
    {
        $bakery = $this->bakery();
        $user = $this->superAdmin();
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);

        // Un compte qui voit les paramètres sans pouvoir administrer les
        // sites : la fiche est affichée, mais aucun formulaire n'est rendu.
        $user->removeRole(Role::SuperAdmin->value);
        $user->syncPermissions(['settings.view', 'bakery.view']);

        $this->actingAs($user)
            ->withSession(['bakery_id' => $bakery->getKey()])
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Boulangerie Soumahoro')
            ->assertSee('Seul un administrateur peut modifier les informations')
            ->assertDontSee(route('settings.site.update'), false);
    }

    public function test_the_super_admin_can_update_the_current_site(): void
    {
        $bakery = $this->bakery();
        $user = $this->superAdmin();
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);

        $this->actingAs($user)
            ->withSession(['bakery_id' => $bakery->getKey()])
            ->put(route('settings.site.update'), $this->payload($bakery, [
                'name' => 'Boulangerie Soumahoro — Centre',
                'phone' => '+225 07 00 00 11',
            ]))
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('status');

        $bakery->refresh();

        $this->assertSame('Boulangerie Soumahoro — Centre', $bakery->name);
        $this->assertSame('+225 07 00 00 11', $bakery->phone);
    }

    public function test_the_update_only_touches_the_current_site(): void
    {
        $current = $this->bakery();
        $other = $this->bakery(['name' => 'Boulangerie Cocody', 'code' => 'BLG-COC']);
        $user = $this->superAdmin();
        $user->bakeries()->attach($current->getKey(), ['is_default' => true]);

        $this->actingAs($user)
            ->withSession(['bakery_id' => $current->getKey()])
            ->put(route('settings.site.update'), $this->payload($current, ['name' => 'Site renommé']))
            ->assertRedirect(route('settings.index'));

        $this->assertSame('Site renommé', $current->refresh()->name);
        $this->assertSame('Boulangerie Cocody', $other->refresh()->name);
    }

    public function test_a_standard_role_cannot_update_a_site(): void
    {
        $bakery = $this->bakery();
        $user = $this->userWithRole(Role::SalesManager);
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);

        $this->actingAs($user)
            ->withSession(['bakery_id' => $bakery->getKey()])
            ->put(route('settings.site.update'), $this->payload($bakery, ['name' => 'Pirate']))
            ->assertForbidden();

        $this->assertSame('Boulangerie Soumahoro', $bakery->refresh()->name);
    }

    public function test_the_update_is_refused_without_a_current_site(): void
    {
        $bakery = $this->bakery();

        $this->actingAs($this->superAdmin())
            ->put(route('settings.site.update'), $this->payload($bakery, ['name' => 'Pirate']))
            ->assertForbidden();

        $this->assertSame('Boulangerie Soumahoro', $bakery->refresh()->name);
    }

    public function test_the_code_must_stay_unique(): void
    {
        $bakery = $this->bakery();
        $this->bakery(['name' => 'Boulangerie Cocody', 'code' => 'BLG-COC']);
        $user = $this->superAdmin();
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);

        $this->actingAs($user)
            ->withSession(['bakery_id' => $bakery->getKey()])
            ->put(route('settings.site.update'), $this->payload($bakery, ['code' => 'BLG-COC']))
            ->assertSessionHasErrors('code');

        $this->assertSame('BLG-ABJ', $bakery->refresh()->code);
    }

    public function test_the_name_is_required(): void
    {
        $bakery = $this->bakery();
        $user = $this->superAdmin();
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);

        $this->actingAs($user)
            ->withSession(['bakery_id' => $bakery->getKey()])
            ->put(route('settings.site.update'), $this->payload($bakery, ['name' => '']))
            ->assertSessionHasErrors('name');
    }

    public function test_the_logo_can_be_uploaded_from_the_settings_screen(): void
    {
        Storage::fake('public');

        $bakery = $this->bakery();
        $user = $this->superAdmin();
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);

        $this->actingAs($user)
            ->withSession(['bakery_id' => $bakery->getKey()])
            ->put(route('settings.site.update'), $this->payload($bakery, ['logo' => $this->png()]))
            ->assertRedirect(route('settings.index'));

        $bakery->refresh();

        $this->assertNotNull($bakery->logo_path);
        $this->assertStringStartsWith('bakeries/', (string) $bakery->logo_path);
        Storage::disk('public')->assertExists((string) $bakery->logo_path);
    }

    public function test_the_logo_is_kept_when_no_file_is_sent(): void
    {
        Storage::fake('public');

        $path = 'bakeries/existant.png';
        Storage::disk('public')->put($path, (string) base64_decode(self::PNG, true));

        $bakery = $this->bakery(['logo_path' => $path]);
        $user = $this->superAdmin();
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);

        $this->actingAs($user)
            ->withSession(['bakery_id' => $bakery->getKey()])
            ->put(route('settings.site.update'), $this->payload($bakery, ['name' => 'Nouveau nom']))
            ->assertRedirect(route('settings.index'));

        $bakery->refresh();

        $this->assertSame('Nouveau nom', $bakery->name);
        $this->assertSame($path, $bakery->logo_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_the_logo_can_be_removed_from_the_settings_screen(): void
    {
        Storage::fake('public');

        $path = 'bakeries/existant.png';
        Storage::disk('public')->put($path, (string) base64_decode(self::PNG, true));

        $bakery = $this->bakery(['logo_path' => $path]);
        $user = $this->superAdmin();
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);

        $this->actingAs($user)
            ->withSession(['bakery_id' => $bakery->getKey()])
            ->put(route('settings.site.update'), $this->payload($bakery, ['remove_logo' => '1']))
            ->assertRedirect(route('settings.index'));

        $bakery->refresh();

        $this->assertNull($bakery->logo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_a_non_image_file_is_refused(): void
    {
        Storage::fake('public');

        $bakery = $this->bakery();
        $user = $this->superAdmin();
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);

        $this->actingAs($user)
            ->withSession(['bakery_id' => $bakery->getKey()])
            ->put(route('settings.site.update'), $this->payload($bakery, [
                'logo' => UploadedFile::fake()->create('notice.pdf', 10, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('logo');

        $this->assertNull($bakery->refresh()->logo_path);
    }
}
