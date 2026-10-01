<?php

declare(strict_types=1);

namespace Tests\Unit\Production;

use App\Domain\Production\RecipeCalculator;
use App\Enums\RecipeDeductionMode;
use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cahier §5, §6 et §37.1 : besoins matières, ressources cumulées,
 * rendement théorique, coût et écart.
 */
class RecipeCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private RecipeCalculator $calculator;

    private Recipe $recipe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = app(RecipeCalculator::class);

        $flour = Product::factory()->create(['name' => 'Farine', 'purchase_price' => 27_500]);
        $glace = Product::factory()->create(['name' => 'Glace', 'purchase_price' => 0]);

        $this->recipe = Recipe::factory()->create(['yield_quantity' => 300]);

        $this->recipe->items()->create([
            'product_id' => $flour->getKey(),
            'quantity' => 1,
            'deduction_mode' => RecipeDeductionMode::Quantity,
            'unit_cost' => null,
            'position' => 0,
        ]);

        $this->recipe->items()->create([
            'product_id' => $glace->getKey(),
            'quantity' => 1,
            'deduction_mode' => RecipeDeductionMode::Monetary,
            'unit_cost' => 2_000,
            'position' => 1,
        ]);
    }

    public function test_requirements_scale_with_the_number_of_batches(): void
    {
        $requirements = $this->calculator->requirements($this->recipe, 3);

        $this->assertCount(2, $requirements);
        $this->assertSame('3', $requirements[0]['planned_quantity']);
        $this->assertSame(82_500, $requirements[0]['line_cost']);
        $this->assertFalse($requirements[0]['deduction_mode']->isCostBased());

        // Ressource monétaire : coût configuré, non déduite du stock.
        $this->assertSame(6_000, $requirements[1]['line_cost']);
        $this->assertFalse($requirements[1]['deduction_mode']->deductsStock());
    }

    public function test_the_theoretical_yield_scales_with_the_batches(): void
    {
        $this->assertSame('300', $this->calculator->theoreticalQuantity($this->recipe, 1));
        $this->assertSame('900', $this->calculator->theoreticalQuantity($this->recipe, 3));
    }

    public function test_the_theoretical_cost_sums_every_resource(): void
    {
        // 1 sac × 27 500 + glace 2 000, le tout × 2 lots.
        $this->assertSame(59_000, $this->calculator->theoreticalCost($this->recipe, 2));
    }

    public function test_the_gap_is_the_real_quantity_minus_the_theoretical_one(): void
    {
        $this->assertSame('-8', $this->calculator->gap('300', '292'));
        $this->assertSame('5', $this->calculator->gap('300', '305'));
        $this->assertSame('0', $this->calculator->gap('300', '300'));
    }
}
