<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Domain\Inventory\StockMovementService;
use App\Enums\StockAlertMode;
use App\Enums\StockAlertStatus;
use App\Enums\StockAlertType;
use App\Enums\StockMovementType;
use App\Models\Bakery;
use App\Models\Product;
use App\Models\StockAlert;
use App\Models\StockAlertRule;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAlertTest extends TestCase
{
    use RefreshDatabase;

    private StockMovementService $movements;

    protected function setUp(): void
    {
        parent::setUp();

        $this->movements = app(StockMovementService::class);
    }

    private function product(array $attributes = []): Product
    {
        $unit = Unit::factory()->create(['code' => 'sack', 'label' => 'Sac', 'symbol' => 'sac']);

        return Product::factory()->create(array_merge(
            ['primary_unit_id' => $unit->getKey(), 'alert_mode' => 'stock_threshold'],
            $attributes
        ));
    }

    private function alert(Product $product, Bakery $bakery, StockAlertType $type): ?StockAlert
    {
        return StockAlert::query()
            ->where('product_id', $product->getKey())
            ->where('bakery_id', $bakery->getKey())
            ->where('type', $type->value)
            ->where('status', '!=', StockAlertStatus::Resolved->value)
            ->first();
    }

    public function test_a_stock_below_the_threshold_opens_a_low_alert_then_resolves_itself(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product(['alert_threshold' => 10]);

        $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 8);

        $this->assertNotNull($this->alert($product, $bakery, StockAlertType::Low));

        $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 5);

        $this->assertNull($this->alert($product, $bakery, StockAlertType::Low));
        $this->assertSame(
            1,
            StockAlert::query()
                ->where('product_id', $product->getKey())
                ->where('type', StockAlertType::Low->value)
                ->where('status', StockAlertStatus::Resolved->value)
                ->count()
        );
    }

    public function test_a_zero_stock_opens_a_critical_alert(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product(['alert_threshold' => 10]);

        $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 1);
        $this->movements->apply($bakery, $product, StockMovementType::SaleOut, 1);

        $this->assertNotNull($this->alert($product, $bakery, StockAlertType::Critical));
    }

    public function test_a_cumulated_rule_triggers_once_the_threshold_is_reached(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        StockAlertRule::factory()->create([
            'product_id' => $product->getKey(),
            'bakery_id' => null,
            'mode' => StockAlertMode::CumulatedThreshold,
            'threshold' => 100,
            'cumulation_period' => 'month',
        ]);

        // Approvisionnement : la consommation cumulée reste nulle.
        $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 300);
        $this->assertNull($this->alert($product, $bakery, StockAlertType::Cumulated));

        // Deux sorties de production : 60 puis 50 → 110 ≥ 100.
        $this->movements->apply($bakery, $product, StockMovementType::ProductionOut, 60);
        $this->assertNull($this->alert($product, $bakery, StockAlertType::Cumulated));

        $this->movements->apply($bakery, $product, StockMovementType::ProductionOut, 50);

        $this->assertNotNull($this->alert($product, $bakery, StockAlertType::Cumulated));
    }

    public function test_a_site_specific_rule_overrides_the_global_rule(): void
    {
        $bakery = Bakery::factory()->create();
        $product = $this->product();

        StockAlertRule::factory()->create([
            'product_id' => $product->getKey(),
            'bakery_id' => null,
            'mode' => StockAlertMode::StockThreshold,
            'threshold' => 100,
        ]);

        StockAlertRule::factory()->create([
            'product_id' => $product->getKey(),
            'bakery_id' => $bakery->getKey(),
            'mode' => StockAlertMode::StockThreshold,
            'threshold' => 5,
        ]);

        // 20 > seuil du site (5) : aucune alerte, bien que 20 < seuil global (100).
        $this->movements->apply($bakery, $product, StockMovementType::SupplierIn, 20);

        $this->assertNull($this->alert($product, $bakery, StockAlertType::Low));
    }
}
