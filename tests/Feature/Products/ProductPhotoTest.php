<?php

declare(strict_types=1);

namespace Tests\Feature\Products;

use App\Enums\ProductType;
use App\Enums\Role;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Photo du produit (cahier §4) : téléversement, remplacement, suppression et
 * affichage dans le catalogue. La photo vit sur le disque `public`
 * (`products/<hash>.<ext>`) et le champ `products.photo_path` est nullable.
 */
class ProductPhotoTest extends TestCase
{
    use RefreshDatabase;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->unit = Unit::factory()->create(['code' => 'kg', 'label' => 'Kilogramme', 'symbol' => 'kg']);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SuperAdmin->value);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'sku' => 'PF-BAGUETTE',
            'name' => 'Baguette',
            'type' => ProductType::FinishedGood->value,
            'primary_unit_id' => $this->unit->getKey(),
            'purchase_price' => 0,
            'sale_price' => 500,
            'wholesale_price' => 350,
            'alert_mode' => 'stock_threshold',
        ], $overrides);
    }

    private function productWithPhoto(): Product
    {
        $path = 'products/baguette.png';
        Storage::disk('public')->put($path, 'contenu');

        return Product::factory()->finishedGood()->create([
            'name' => 'Baguette',
            'primary_unit_id' => $this->unit->getKey(),
            'photo_path' => $path,
        ]);
    }

    public function test_a_photo_can_be_uploaded_when_creating_a_product(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superAdmin())
            ->post(route('products.store'), $this->payload([
                'photo' => UploadedFile::fake()->image('baguette.png', 200, 200),
            ]))
            ->assertRedirect(route('products.index', ['group' => 'products']));

        $product = Product::query()->firstOrFail();

        $this->assertNotNull($product->photo_path);
        $this->assertStringStartsWith('products/', $product->photo_path);
        Storage::disk('public')->assertExists($product->photo_path);
    }

    public function test_a_product_can_be_saved_without_a_photo(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superAdmin())
            ->post(route('products.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertNull(Product::query()->firstOrFail()->photo_path);
    }

    public function test_an_image_that_is_not_a_picture_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superAdmin())
            ->post(route('products.store'), $this->payload([
                'photo' => UploadedFile::fake()->create('fiche.pdf', 120, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('products', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('products'));
    }

    public function test_a_new_photo_replaces_the_previous_file(): void
    {
        Storage::fake('public');
        $product = $this->productWithPhoto();
        $previous = (string) $product->photo_path;

        $this->actingAs($this->superAdmin())
            ->put(route('products.update', $product), $this->payload([
                'sku' => $product->sku,
                'type' => $product->type->value,
                'photo' => UploadedFile::fake()->image('autre.png', 120, 120),
                'remove_photo' => 0,
            ]))
            ->assertRedirect(route('products.index'));

        $product->refresh();

        $this->assertNotNull($product->photo_path);
        $this->assertNotSame($previous, $product->photo_path);
        Storage::disk('public')->assertExists($product->photo_path);
        Storage::disk('public')->assertMissing($previous);
    }

    public function test_the_photo_can_be_removed(): void
    {
        Storage::fake('public');
        $product = $this->productWithPhoto();
        $previous = (string) $product->photo_path;

        $this->actingAs($this->superAdmin())
            ->put(route('products.update', $product), $this->payload([
                'sku' => $product->sku,
                'type' => $product->type->value,
                'remove_photo' => '1',
            ]))
            ->assertRedirect(route('products.index'));

        $product->refresh();

        $this->assertNull($product->photo_path);
        Storage::disk('public')->assertMissing($previous);
    }

    public function test_the_edit_screen_offers_to_remove_an_existing_photo(): void
    {
        Storage::fake('public');
        $product = $this->productWithPhoto();

        $this->actingAs($this->superAdmin())
            ->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('multipart/form-data', false)
            ->assertSee($product->photoUrl(), false)
            ->assertSee('Supprimer la photo');
    }

    public function test_the_edit_screen_hides_the_removal_option_without_a_photo(): void
    {
        $product = Product::factory()->create(['primary_unit_id' => $this->unit->getKey()]);

        $this->actingAs($this->superAdmin())
            ->get(route('products.edit', $product))
            ->assertOk()
            ->assertDontSee('Supprimer la photo');
    }

    public function test_the_catalogue_shows_the_product_photo(): void
    {
        Storage::fake('public');
        $product = $this->productWithPhoto();

        $this->actingAs($this->superAdmin())
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee($product->photoUrl(), false);
    }

    public function test_a_product_without_a_photo_falls_back_to_an_icon(): void
    {
        Product::factory()->finishedGood()->create([
            'name' => 'Baguette',
            'primary_unit_id' => $this->unit->getKey(),
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('products.index'))
            ->assertOk()
            // Sans photo, le catalogue rend une icône et non une image.
            ->assertDontSee('<img', false);
    }
}
