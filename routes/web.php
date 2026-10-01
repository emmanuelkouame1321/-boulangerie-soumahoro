<?php

declare(strict_types=1);

use App\Http\Controllers\BakeryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DayClosingController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LossController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionOrderController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockAlertController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnitConversionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WholesalerController;
use App\Http\Controllers\WholesalerRebateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes web
|--------------------------------------------------------------------------
| Mapping des écrans de « BakeryOS – Prototype (charte caisse).html ».
|
| Toute route métier est derrière `auth` et, quand elle existe, derrière
| une permission vérifiée côté serveur (cahier §20 et §31). Le Super-Admin
| passe toutes les permissions via Gate::before.
*/

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function (): void {
    Route::redirect('/', '/dashboard');

    Route::view('/dashboard', 'dashboard.index')->name('dashboard');

    // Caisse (POS) — plein écran, hors gabarit applicatif.
    Route::view('/caisse', 'sales.pos')->name('pos')->middleware('permission:pos.open');

    // Production (cahier §7 et §8) : ordres, assistant, recettes.
    Route::get('/production', [ProductionOrderController::class, 'index'])
        ->name('production.index')
        ->middleware('permission:production.view');

    Route::get('/production/recipes', [RecipeController::class, 'index'])
        ->name('production.recipes.index')
        ->middleware('permission:recipe.view');

    Route::get('/production/create', [ProductionOrderController::class, 'create'])
        ->name('production.create')
        ->middleware('permission:production.create');

    Route::post('/production', [ProductionOrderController::class, 'store'])
        ->name('production.store')
        ->middleware('permission:production.create');

    Route::get('/production/{order}', [ProductionOrderController::class, 'show'])
        ->name('production.show')
        ->whereNumber('order')
        ->middleware('permission:production.view');

    // Stocks (cahier §9) : stock temps réel, grand livre, ajustements.
    Route::get('/inventory', [InventoryController::class, 'index'])
        ->name('inventory.index')
        ->middleware('permission:inventory.view');

    Route::get('/inventory/movements', [StockMovementController::class, 'index'])
        ->name('inventory.movements')
        ->middleware('permission:inventory.view');

    Route::post('/inventory/adjustments', [StockAdjustmentController::class, 'store'])
        ->name('inventory.adjustments.store')
        ->middleware('permission:inventory.adjust');
    // Achats : fournisseurs, commandes, réception (cahier §10 et §11).
    Route::get('/purchasing/orders', [PurchaseOrderController::class, 'index'])
        ->name('purchasing.orders.index')
        ->middleware('permission:purchasing.view');

    Route::get('/purchasing/orders/create', [PurchaseOrderController::class, 'create'])
        ->name('purchasing.orders.create')
        ->middleware('permission:purchasing.create');

    Route::post('/purchasing/orders', [PurchaseOrderController::class, 'store'])
        ->name('purchasing.orders.store')
        ->middleware('permission:purchasing.create');

    Route::get('/purchasing/orders/{order}', [PurchaseOrderController::class, 'show'])
        ->name('purchasing.orders.show')
        ->whereNumber('order')
        ->middleware('permission:purchasing.view');

    Route::post('/purchasing/orders/{order}/cancel', [PurchaseOrderController::class, 'cancel'])
        ->name('purchasing.orders.cancel')
        ->whereNumber('order')
        ->middleware('permission:purchasing.manage');

    Route::post('/purchasing/orders/{order}/receipts', [GoodsReceiptController::class, 'store'])
        ->name('purchasing.receipts.store')
        ->whereNumber('order')
        ->middleware('permission:goods-receipt.create');
    // Ventes (cahier §14 et §15) : ventes au détail et clôtures journalières.
    Route::get('/sales/closings', [DayClosingController::class, 'index'])
        ->name('sales.closings.index')
        ->middleware('permission:sales.close-day');

    Route::get('/sales/closings/create', [DayClosingController::class, 'create'])
        ->name('sales.closings.create')
        ->middleware('permission:sales.close-day');

    Route::post('/sales/closings', [DayClosingController::class, 'store'])
        ->name('sales.closings.store')
        ->middleware('permission:sales.close-day');

    Route::get('/sales/closings/{closing}', [DayClosingController::class, 'show'])
        ->name('sales.closings.show')
        ->whereNumber('closing')
        ->middleware('permission:sales.close-day');

    Route::get('/sales', [SaleController::class, 'index'])
        ->name('sales.index')
        ->middleware('permission:sales.view');

    Route::get('/sales/create', [SaleController::class, 'create'])
        ->name('sales.create')
        ->middleware('permission:sales.create');

    Route::post('/sales', [SaleController::class, 'store'])
        ->name('sales.store')
        ->middleware('permission:sales.create');

    Route::get('/sales/{sale}', [SaleController::class, 'show'])
        ->name('sales.show')
        ->whereNumber('sale')
        ->middleware('permission:sales.view');

    // Pertes et invendus (cahier §8.2 et §15).
    Route::get('/losses', [LossController::class, 'index'])
        ->name('losses.index')
        ->middleware('permission:loss.view');

    Route::get('/losses/create', [LossController::class, 'create'])
        ->name('losses.create')
        ->middleware('permission:loss.create');

    Route::post('/losses', [LossController::class, 'store'])
        ->name('losses.store')
        ->middleware('permission:loss.create');

    Route::get('/losses/{loss}', [LossController::class, 'show'])
        ->name('losses.show')
        ->whereNumber('loss')
        ->middleware('permission:loss.view');

    // Commercial
    Route::get('/customers', [CustomerController::class, 'index'])
        ->name('customers.index')
        ->middleware('permission:customer.view');

    Route::get('/customers/create', [CustomerController::class, 'create'])
        ->name('customers.create')
        ->middleware('permission:customer.manage');

    Route::post('/customers', [CustomerController::class, 'store'])
        ->name('customers.store')
        ->middleware('permission:customer.manage');

    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])
        ->name('customers.edit')
        ->whereNumber('customer')
        ->middleware('permission:customer.manage');

    Route::put('/customers/{customer}', [CustomerController::class, 'update'])
        ->name('customers.update')
        ->whereNumber('customer')
        ->middleware('permission:customer.manage');
    // Ressources — grossistes (cahier §12).
    Route::get('/wholesalers', [WholesalerController::class, 'index'])
        ->name('wholesalers.index')
        ->middleware('permission:wholesaler.view');

    Route::get('/wholesalers/create', [WholesalerController::class, 'create'])
        ->name('wholesalers.create')
        ->middleware('permission:wholesaler.manage');

    Route::post('/wholesalers', [WholesalerController::class, 'store'])
        ->name('wholesalers.store')
        ->middleware('permission:wholesaler.manage');

    Route::get('/wholesalers/{wholesaler}', [WholesalerController::class, 'show'])
        ->name('wholesalers.show')
        ->whereNumber('wholesaler')
        ->middleware('permission:wholesaler.view');

    Route::get('/wholesalers/{wholesaler}/edit', [WholesalerController::class, 'edit'])
        ->name('wholesalers.edit')
        ->whereNumber('wholesaler')
        ->middleware('permission:wholesaler.manage');

    Route::put('/wholesalers/{wholesaler}', [WholesalerController::class, 'update'])
        ->name('wholesalers.update')
        ->whereNumber('wholesaler')
        ->middleware('permission:wholesaler.manage');

    // Ristournes annuelles (cahier §13).
    Route::get('/rebates', [WholesalerRebateController::class, 'index'])
        ->name('rebates.index')
        ->middleware('permission:rebate.view');

    Route::post('/rebates/rules', [WholesalerRebateController::class, 'storeRule'])
        ->name('rebates.rules.store')
        ->middleware('permission:rebate.manage');

    Route::put('/rebates/rules/{rule}', [WholesalerRebateController::class, 'updateRule'])
        ->name('rebates.rules.update')
        ->whereNumber('rule')
        ->middleware('permission:rebate.manage');

    Route::delete('/rebates/rules/{rule}', [WholesalerRebateController::class, 'destroyRule'])
        ->name('rebates.rules.destroy')
        ->whereNumber('rule')
        ->middleware('permission:rebate.manage');

    Route::post('/rebates/compute', [WholesalerRebateController::class, 'compute'])
        ->name('rebates.compute')
        ->middleware('permission:rebate.manage');

    Route::post('/rebates/{rebate}/validate', [WholesalerRebateController::class, 'validateRebate'])
        ->name('rebates.validate')
        ->whereNumber('rebate')
        ->middleware('permission:rebate.manage');

    Route::post('/rebates/{rebate}/settle', [WholesalerRebateController::class, 'settle'])
        ->name('rebates.settle')
        ->whereNumber('rebate')
        ->middleware('permission:rebate.manage');

    // Ressources — fournisseurs (cahier §10).
    Route::get('/suppliers', [SupplierController::class, 'index'])
        ->name('suppliers.index')
        ->middleware('permission:supplier.view');

    Route::get('/suppliers/create', [SupplierController::class, 'create'])
        ->name('suppliers.create')
        ->middleware('permission:supplier.manage');

    Route::post('/suppliers', [SupplierController::class, 'store'])
        ->name('suppliers.store')
        ->middleware('permission:supplier.manage');

    Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])
        ->name('suppliers.edit')
        ->whereNumber('supplier')
        ->middleware('permission:supplier.manage');

    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])
        ->name('suppliers.update')
        ->whereNumber('supplier')
        ->middleware('permission:supplier.manage');
    Route::view('/employees', 'employees.index')->name('employees.index')->middleware('permission:employee.view');
    Route::view('/payroll', 'payroll.index')->name('payroll.index')->middleware('permission:payroll.view');
    Route::view('/expenses', 'expenses.index')->name('expenses.index')->middleware('permission:expense.view');

    // Analyse
    Route::view('/reports', 'reports.index')->name('reports.index')->middleware('permission:report.view');
    Route::get('/notifications', [StockAlertController::class, 'index'])
        ->name('notifications.index')
        ->middleware('permission:alert.view');

    Route::post('/notifications/{alert}/acknowledge', [StockAlertController::class, 'acknowledge'])
        ->name('notifications.acknowledge')
        ->whereNumber('alert')
        ->middleware('permission:alert.acknowledge');

    // Administration
    Route::view('/settings', 'settings.index')->name('settings.index')->middleware('permission:settings.view');
    Route::view('/settings/audit', 'settings.audit.index')->name('settings.audit.index')->middleware('permission:audit.view');

    // Utilisateurs, rôles et permissions (cahier §20.4).
    Route::resource('settings/users', UserController::class)
        ->except('show', 'destroy')
        ->names('settings.users')
        ->whereNumber('user')
        ->middleware('permission:user.view');

    Route::resource('settings/roles', RoleController::class)
        ->except('show', 'destroy')
        ->names('settings.roles')
        ->whereNumber('role')
        ->middleware('permission:role.view');

    // Catalogue produits et matières premières (cahier §4).
    Route::resource('products', ProductController::class)
        ->except('show', 'destroy')
        ->middleware('permission:product.view');

    // Administration des boulangeries — réservé au Super-Admin (cahier §19.2).
    Route::resource('settings/bakeries', BakeryController::class)
        ->except('show')
        ->names('bakeries');

    // Paramétrage : unités de mesure et conversions (cahier §4.3).
    // Les routes de conversion sont déclarées avant la ressource pour ne
    // pas être capturées par « settings/units/{unit} ».
    Route::post('/settings/units/conversions', [UnitConversionController::class, 'store'])
        ->name('units.conversions.store')
        ->middleware('permission:unit.manage');

    Route::delete('/settings/units/conversions/{conversion}', [UnitConversionController::class, 'destroy'])
        ->name('units.conversions.destroy')
        ->middleware('permission:unit.manage');

    Route::resource('settings/units', UnitController::class)
        ->except('show', 'destroy')
        ->names('units')
        ->where(['unit' => '[0-9]+'])
        ->middleware('permission:unit.view');
});
