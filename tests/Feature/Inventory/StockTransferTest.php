<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Domain\Inventory\StockMovementService;
use App\Domain\Inventory\StockService;
use App\Domain\Inventory\StockTransferService;
use App\Enums\ProductType;
use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
use App\Exceptions\InsufficientLotException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStockTransferException;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Unit;
use App\Models\User;
use App\Support\Decimal;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    private StockTransferService $transfers;

    private StockMovementService $movements;

    private StockService $stocks;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->transfers = app(StockTransferService::class);
        $this->movements = app(StockMovementService::class);
        $this->stocks = app(StockService::class);
    }

    private function product(ProductType $type = ProductType::RawMaterial): Product
    {
        $unit = Unit::factory()->create(['code' => 'kg', 'label' => 'Kilogramme', 'symbol' => 'kg']);

        return Product::factory()->create([
            'type' => $type,
            'primary_unit_id' => $unit->getKey(),
        ]);
    }

    private function user(Role $role, Bakery $bakery): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        $user->bakeries()->attach($bakery->getKey(), ['is_default' => true]);

        return $user;
    }

    public function test_sending_a_transfer_moves_stock_out_of_the_source_only(): void
    {
        $source = Bakery::factory()->create();
        $destination = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($source, $product, StockMovementType::SupplierIn, 100);

        $transfer = $this->transfers->create($source, $destination, $product, 30);
        $this->transfers->send($transfer);

        $this->assertSame('70', Decimal::trim($this->stocks->physical($product, $source)));
        $this->assertSame('0', Decimal::trim($this->stocks->physical($product, $destination)));
        $this->assertSame(StockTransferStatus::Sent, $transfer->refresh()->status);

        $this->assertTrue(StockMovement::query()
            ->where('type', StockMovementType::TransferOut->value)
            ->where('bakery_id', $source->getKey())
            ->where('reference', $transfer->reference)
            ->exists());
    }

    public function test_receiving_a_transfer_moves_stock_into_the_destination(): void
    {
        $source = Bakery::factory()->create();
        $destination = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($source, $product, StockMovementType::SupplierIn, 100);

        $transfer = $this->transfers->create($source, $destination, $product, 30);
        $transfer = $this->transfers->send($transfer);
        $transfer = $this->transfers->receive($transfer);

        $this->assertSame('70', Decimal::trim($this->stocks->physical($product, $source)));
        $this->assertSame('30', Decimal::trim($this->stocks->physical($product, $destination)));
        $this->assertSame(StockTransferStatus::Received, $transfer->status);
    }

    public function test_a_transfer_between_the_same_site_is_refused(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        $this->expectException(InvalidStockTransferException::class);

        $this->transfers->create($bakery, $bakery, $product, 5);
    }

    public function test_a_non_positive_quantity_is_refused(): void
    {
        $product = $this->product();

        $this->expectException(InvalidStockTransferException::class);

        $this->transfers->create(Bakery::factory()->create(), Bakery::factory()->create(), $product, 0);
    }

    public function test_a_batch_from_another_site_is_refused(): void
    {
        $source = Bakery::factory()->create();
        $other = Bakery::factory()->create();
        $product = $this->product(ProductType::FinishedGood);

        $batch = ProductionBatch::factory()->create([
            'bakery_id' => $other->getKey(),
            'product_id' => $product->getKey(),
            'available_quantity' => 50,
        ]);

        $this->expectException(InvalidStockTransferException::class);

        $this->transfers->create($source, Bakery::factory()->create(), $product, 10, $batch);
    }

    public function test_receiving_a_draft_transfer_is_refused(): void
    {
        $product = $this->product();

        $transfer = $this->transfers->create(
            Bakery::factory()->create(),
            Bakery::factory()->create(),
            $product,
            10,
        );

        $this->expectException(InvalidStockTransferException::class);

        $this->transfers->receive($transfer);
    }

    public function test_a_draft_transfer_can_be_cancelled(): void
    {
        $product = $this->product();

        $transfer = $this->transfers->create(
            Bakery::factory()->create(),
            Bakery::factory()->create(),
            $product,
            10,
        );

        $cancelled = $this->transfers->cancel($transfer);

        $this->assertSame(StockTransferStatus::Cancelled, $cancelled->status);
    }

    public function test_sending_twice_does_not_double_the_movement(): void
    {
        $source = Bakery::factory()->create();
        $destination = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($source, $product, StockMovementType::SupplierIn, 100);

        $transfer = $this->transfers->create($source, $destination, $product, 30);
        $transfer = $this->transfers->send($transfer);

        try {
            $this->transfers->send($transfer);
            $this->fail('Un second envoi aurait dû être refusé.');
        } catch (InvalidStockTransferException) {
            // Attendu : le transfert n'est plus un brouillon.
        }

        $this->assertSame('70', Decimal::trim($this->stocks->physical($product, $source)));
        $this->assertSame(1, StockMovement::query()
            ->where('type', StockMovementType::TransferOut->value)
            ->where('reference', $transfer->reference)
            ->count());
    }

    public function test_a_designated_batch_is_transferred_and_recreated_at_the_destination(): void
    {
        $source = Bakery::factory()->create();
        $destination = Bakery::factory()->create();
        $product = $this->product(ProductType::FinishedGood);

        $expiresOn = now()->addDays(3)->toDateString();

        $batch = ProductionBatch::factory()->create([
            'bakery_id' => $source->getKey(),
            'product_id' => $product->getKey(),
            'available_quantity' => 100,
            'initial_quantity' => 100,
            'expires_on' => $expiresOn,
        ]);

        $this->movements->apply($source, $product, StockMovementType::SupplierIn, 100);

        $transfer = $this->transfers->create($source, $destination, $product, 30, $batch);
        $transfer = $this->transfers->send($transfer);

        $this->assertSame('70', Decimal::trim($batch->refresh()->available_quantity));
        $this->assertSame('30', Decimal::trim($batch->transferred_quantity));

        $this->transfers->receive($transfer);

        $received = ProductionBatch::withoutBakeryScope()
            ->where('bakery_id', $destination->getKey())
            ->where('reference', $transfer->reference)
            ->first();

        $this->assertNotNull($received);
        $this->assertSame('30', Decimal::trim($received->available_quantity));
        $this->assertSame($expiresOn, $received->expires_on?->toDateString());
    }

    public function test_a_lot_smaller_than_the_requested_quantity_is_refused(): void
    {
        $source = Bakery::factory()->create();
        $product = $this->product(ProductType::FinishedGood);

        $batch = ProductionBatch::factory()->create([
            'bakery_id' => $source->getKey(),
            'product_id' => $product->getKey(),
            'available_quantity' => 5,
        ]);

        $this->expectException(InsufficientLotException::class);

        $this->transfers->create($source, Bakery::factory()->create(), $product, 10, $batch);
    }

    public function test_a_manager_can_create_a_transfer_from_the_screen(): void
    {
        $source = Bakery::factory()->create();
        $destination = Bakery::factory()->create();
        $product = $this->product();

        $this->actingAs($this->user(Role::ProductionManager, $source))
            ->post(route('inventory.transfers.store'), [
                'destination_bakery_id' => $destination->getKey(),
                'product_id' => $product->getKey(),
                'quantity' => 12,
                'reason' => 'Réassort',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('stock_transfers', [
            'bakery_id' => $source->getKey(),
            'destination_bakery_id' => $destination->getKey(),
            'product_id' => $product->getKey(),
            'status' => StockTransferStatus::Draft->value,
            'reason' => 'Réassort',
        ]);
    }

    public function test_the_transfer_screens_render(): void
    {
        $source = Bakery::factory()->create();
        Bakery::factory()->create();
        $this->product(ProductType::FinishedGood);

        $user = $this->user(Role::ProductionManager, $source);

        $this->actingAs($user)
            ->get(route('inventory.transfers.index'))
            ->assertOk()
            ->assertSee('Transferts inter-sites');

        $this->actingAs($user)
            ->get(route('inventory.transfers.create'))
            ->assertOk()
            ->assertSee('Nouveau transfert');
    }

    public function test_a_transfer_touching_a_foreign_site_is_not_visible(): void
    {
        $source = Bakery::factory()->create();
        $destination = Bakery::factory()->create();
        $foreign = Bakery::factory()->create();
        $product = $this->product();

        $transfer = $this->transfers->create($source, $destination, $product, 10);

        // Un utilisateur du site expéditeur voit le transfert.
        $this->actingAs($this->user(Role::ProductionManager, $source))
            ->get(route('inventory.transfers.show', $transfer))
            ->assertOk();

        // Un utilisateur d'un site tiers obtient un 404 (portée de route).
        $this->actingAs($this->user(Role::ProductionManager, $foreign))
            ->get(route('inventory.transfers.show', $transfer))
            ->assertNotFound();
    }

    public function test_the_destination_site_cannot_send_a_transfer(): void
    {
        $source = Bakery::factory()->create();
        $destination = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($source, $product, StockMovementType::SupplierIn, 100);

        $transfer = $this->transfers->create($source, $destination, $product, 10);

        // Le destinataire peut consulter mais pas expédier à la place de la source.
        $this->actingAs($this->user(Role::ProductionManager, $destination))
            ->post(route('inventory.transfers.send', $transfer))
            ->assertForbidden();

        $this->assertSame(StockTransferStatus::Draft, $transfer->refresh()->status);
    }

    public function test_the_destination_site_can_receive_a_sent_transfer(): void
    {
        $source = Bakery::factory()->create();
        $destination = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($source, $product, StockMovementType::SupplierIn, 100);

        $transfer = $this->transfers->create($source, $destination, $product, 10);
        $transfer = $this->transfers->send($transfer);

        $this->actingAs($this->user(Role::SalesManager, $destination))
            ->post(route('inventory.transfers.receive', $transfer))
            ->assertRedirect(route('inventory.transfers.show', $transfer));

        $this->assertSame(StockTransferStatus::Received, $transfer->refresh()->status);
    }

    /**
     * Deux transferts préparés sur le même stock ne peuvent pas être
     * expédiés tous les deux : le verrou de ligne au moment de
     * l'expédition garantit que le stock ne devient jamais négatif
     * (cahier §28). Un test réellement parallèle relève de la CI MySQL ;
     * SQLite en mémoire sérialise les écritures, on vérifie donc ici le
     * garde-fou transactionnel lui-même.
     */
    public function test_two_simultaneous_shipments_cannot_both_succeed(): void
    {
        $source = Bakery::factory()->create();
        $destination = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($source, $product, StockMovementType::SupplierIn, 100);

        // Les deux brouillons sont créés avant toute expédition : seule
        // la relecture verrouillée de départage.
        $first = $this->transfers->create($source, $destination, $product, 60);
        $second = $this->transfers->create($source, $destination, $product, 60);

        $this->transfers->send($first);

        try {
            $this->transfers->send($second);
            $this->fail('Une seconde expédition aurait dû être refusée.');
        } catch (InsufficientStockException) {
            // Attendu : le stock du site expéditeur est insuffisant.
        }

        $this->assertSame('40', Decimal::trim($this->stocks->physical($product, $source)));
        $this->assertSame(1, StockMovement::query()
            ->where('type', StockMovementType::TransferOut->value)
            ->count());
        $this->assertSame(StockTransferStatus::Draft, $second->refresh()->status);
    }

    /**
     * Deux expéditions d'un même lot ne peuvent pas le sur-consommer : la
     * quantité disponible est relue sous verrou avant chaque sortie.
     */
    public function test_two_shipments_from_the_same_batch_cannot_over_consume_it(): void
    {
        $source = Bakery::factory()->create();
        $destination = Bakery::factory()->create();
        $product = $this->product(ProductType::FinishedGood);

        $batch = ProductionBatch::factory()->create([
            'bakery_id' => $source->getKey(),
            'product_id' => $product->getKey(),
            'available_quantity' => 100,
            'initial_quantity' => 100,
        ]);

        $this->movements->apply($source, $product, StockMovementType::SupplierIn, 100);

        $first = $this->transfers->create($source, $destination, $product, 60, $batch);
        $second = $this->transfers->create($source, $destination, $product, 60, $batch);

        $this->transfers->send($first);

        try {
            $this->transfers->send($second);
            $this->fail('Une seconde expédition du même lot aurait dû être refusée.');
        } catch (InsufficientLotException) {
            // Attendu : il ne reste que 40 unités dans le lot.
        }

        $this->assertSame('40', Decimal::trim($batch->refresh()->available_quantity));
        $this->assertSame('60', Decimal::trim($batch->transferred_quantity));
        $this->assertSame(1, StockMovement::query()
            ->where('type', StockMovementType::TransferOut->value)
            ->count());
    }

    /**
     * Une seconde expédition portant sur un état périmé (statut encore
     * « brouillon » en mémoire) ne double pas le mouvement : le service
     * relit et verrouille le document avant d'agir.
     */
    public function test_replaying_a_shipment_through_a_stale_instance_does_not_double_the_movement(): void
    {
        $source = Bakery::factory()->create();
        $destination = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($source, $product, StockMovementType::SupplierIn, 100);

        $transfer = $this->transfers->create($source, $destination, $product, 30);
        $stale = StockTransfer::query()->findOrFail($transfer->getKey());

        $this->transfers->send($transfer);

        try {
            $this->transfers->send($stale);
            $this->fail('Un rejeu sur un état périmé aurait dû être refusé.');
        } catch (InvalidStockTransferException) {
            // Attendu : le document n'est plus un brouillon.
        }

        $this->assertSame('70', Decimal::trim($this->stocks->physical($product, $source)));
        $this->assertSame(StockTransferStatus::Sent, $stale->refresh()->status);
        $this->assertSame(1, StockMovement::query()
            ->where('type', StockMovementType::TransferOut->value)
            ->where('reference', $transfer->reference)
            ->count());
    }

    /**
     * Deux réceptions simultanées du même transfert n'entrent le stock
     * qu'une fois : le statut verrouillé refuse la seconde, sans quoi la
     * réception serait comptée deux fois chez le destinataire.
     */
    public function test_two_simultaneous_receptions_cannot_double_the_entry(): void
    {
        $source = Bakery::factory()->create();
        $destination = Bakery::factory()->create();
        $product = $this->product();

        $this->movements->apply($source, $product, StockMovementType::SupplierIn, 100);

        $transfer = $this->transfers->create($source, $destination, $product, 30);
        $transfer = $this->transfers->send($transfer);

        // Deux instances « expédiées » lues avant la première réception.
        $parallel = StockTransfer::query()->findOrFail($transfer->getKey());

        $this->transfers->receive($transfer);

        try {
            $this->transfers->receive($parallel);
            $this->fail('Une seconde réception aurait dû être refusée.');
        } catch (InvalidStockTransferException) {
            // Attendu : le transfert est déjà reçu.
        }

        $this->assertSame('30', Decimal::trim($this->stocks->physical($product, $destination)));
        $this->assertSame(1, StockMovement::query()
            ->where('type', StockMovementType::TransferIn->value)
            ->where('reference', $transfer->reference)
            ->count());
    }
}
