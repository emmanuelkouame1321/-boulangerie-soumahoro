<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\ReportingService;
use App\Enums\Role;
use App\Models\Bakery;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Export;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    private Bakery $bakery;

    private Product $bread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->bakery = Bakery::factory()->create();
        $unit = Unit::factory()->create(['code' => 'piece', 'label' => 'Pièce', 'symbol' => 'pcs']);

        $this->bread = Product::factory()->create([
            'name' => 'Baguette',
            'primary_unit_id' => $unit->getKey(),
            'sale_price' => 500,
            'purchase_price' => 300,
        ]);
    }

    private function sale(int $quantity, int $unitPrice, string $date, ?string $type = 'retail'): Sale
    {
        $total = $quantity * $unitPrice;

        $sale = Sale::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'type' => $type,
            'sale_date' => $date,
            'subtotal' => $total,
            'total' => $total,
            'paid_total' => $total,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->getKey(),
            'product_id' => $this->bread->getKey(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $quantity * $unitPrice,
        ]);

        return $sale;
    }

    private function service(): ReportingService
    {
        return app(ReportingService::class);
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);
        $user->bakeries()->attach($this->bakery->getKey(), ['is_default' => true]);

        return $user;
    }

    public function test_the_sales_report_aggregates_revenue_and_products(): void
    {
        $this->sale(10, 500, now()->toDateString());
        $this->sale(4, 500, now()->toDateString(), 'wholesale');

        $report = $this->service()->sales($this->bakery->getKey(), now()->toDateString(), now()->toDateString());

        $this->assertSame(7_000, $report['revenue']);
        $this->assertSame(2, $report['sales']);
        $this->assertSame(5_000, $report['retail_revenue']);
        $this->assertSame(2_000, $report['wholesale_revenue']);
        $this->assertSame(0, bccomp($report['quantity'], '14', 4));
        $this->assertSame('Baguette', $report['by_product'][0]['name']);
    }

    public function test_the_expenses_report_groups_by_category(): void
    {
        $category = ExpenseCategory::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'name' => 'Électricité',
        ]);

        Expense::factory()->create([
            'bakery_id' => $this->bakery->getKey(),
            'expense_category_id' => $category->getKey(),
            'amount' => 120_000,
            'spent_on' => now()->toDateString(),
        ]);

        $report = $this->service()->expenses($this->bakery->getKey(), now()->startOfMonth()->toDateString(), now()->toDateString());

        $this->assertSame(120_000, $report['total']);
        $this->assertSame('Électricité', $report['by_category'][0]['name']);
        $this->assertSame(120_000, $report['by_category'][0]['total']);
    }

    public function test_the_period_answers_the_seventeen_questions_of_the_specification(): void
    {
        $this->sale(10, 500, now()->toDateString());

        $answers = $this->service()->questions($this->bakery->getKey());

        $this->assertCount(17, $answers);

        foreach ($answers as $entry) {
            $this->assertNotSame('', trim($entry['question']));
            $this->assertNotSame('', trim($entry['answer']));
        }
    }

    public function test_a_super_admin_can_see_the_reports_and_dashboard(): void
    {
        $admin = $this->user(Role::SuperAdmin);

        $this->actingAs($admin)->get(route('reports.index'))->assertOk()->assertSee('Questions du cahier');
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();

        foreach (['sales', 'stock', 'production', 'losses', 'purchases', 'wholesalers', 'expenses', 'payroll'] as $kind) {
            $this->actingAs($admin)
                ->get(route('reports.show', ['kind' => $kind]))
                ->assertOk();
        }
    }

    public function test_a_report_can_be_exported_as_csv_and_excel(): void
    {
        $this->sale(10, 500, now()->toDateString());
        $admin = $this->user(Role::SuperAdmin);
        Storage::fake('local');

        // CSV et Excel partent en file d'attente (ADR-021) : le fichier
        // devient téléchargeable depuis l'écran Téléchargements.
        foreach (['csv', 'xls'] as $format) {
            $this->actingAs($admin)
                ->post(route('exports.store'), [
                    'kind' => 'report:sales',
                    'format' => $format,
                    'filters' => [
                        'from' => now()->startOfMonth()->toDateString(),
                        'to' => now()->toDateString(),
                    ],
                ])
                ->assertRedirect(route('exports.index'));
        }

        $exports = Export::withoutBakeryScope()->orderBy('id')->get();

        $this->assertCount(2, $exports);

        foreach ($exports as $export) {
            $this->assertSame(Export::STATUS_READY, $export->status);

            $this->actingAs($admin)
                ->get(route('exports.download', $export))
                ->assertOk()
                ->assertDownload();

            $content = Storage::disk('local')->get((string) $export->path);

            if ($export->format === 'xls') {
                $this->assertStringContainsString('<Workbook', $content);
            } else {
                $this->assertStringContainsString('Produit', $content);
            }
        }

        // L'impression reste directe : le navigateur imprime en PDF.
        $this->actingAs($admin)
            ->get(route('reports.print', ['kind' => 'sales']))
            ->assertOk()
            ->assertSee('Imprimer');
    }

    public function test_an_export_requires_the_right_permission(): void
    {
        $production = $this->user(Role::ProductionManager);

        // Pas de permission report.export : la création est refusée.
        $this->actingAs($production)
            ->post(route('exports.store'), ['kind' => 'report:sales', 'format' => 'csv'])
            ->assertForbidden();
    }

    public function test_reports_require_the_report_permission(): void
    {
        $this->actingAs($this->user(Role::ProductionManager))
            ->get(route('reports.index'))
            ->assertForbidden();

        $this->actingAs($this->user(Role::SalesManager))
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_reports_require_authentication(): void
    {
        $this->get(route('reports.index'))->assertRedirect('/login');
        $this->get(route('dashboard'))->assertRedirect('/login');
    }
}
