<?php

declare(strict_types=1);

use App\Http\Controllers\BakeryController;
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

    // Opérations
    Route::view('/production', 'production.index')->name('production.index')->middleware('permission:production.view');
    Route::view('/inventory', 'inventory.index')->name('inventory.index')->middleware('permission:inventory.view');
    Route::view('/purchasing/orders', 'purchasing.orders.index')->name('purchasing.orders.index')->middleware('permission:purchasing.view');
    Route::view('/sales', 'sales.index')->name('sales.index')->middleware('permission:sales.view');
    Route::view('/losses', 'losses.index')->name('losses.index')->middleware('permission:loss.view');

    // Commercial
    Route::view('/customers', 'customers.index')->name('customers.index')->middleware('permission:customer.view');
    Route::view('/wholesalers', 'wholesalers.index')->name('wholesalers.index')->middleware('permission:wholesaler.view');
    Route::view('/rebates', 'rebates.index')->name('rebates.index')->middleware('permission:rebate.view');

    // Ressources
    Route::view('/suppliers', 'suppliers.index')->name('suppliers.index')->middleware('permission:supplier.view');
    Route::view('/employees', 'employees.index')->name('employees.index')->middleware('permission:employee.view');
    Route::view('/payroll', 'payroll.index')->name('payroll.index')->middleware('permission:payroll.view');
    Route::view('/expenses', 'expenses.index')->name('expenses.index')->middleware('permission:expense.view');

    // Analyse
    Route::view('/reports', 'reports.index')->name('reports.index')->middleware('permission:report.view');
    Route::view('/notifications', 'notifications.index')->name('notifications.index')->middleware('permission:alert.view');

    // Administration
    Route::view('/settings', 'settings.index')->name('settings.index')->middleware('permission:settings.view');
    Route::view('/settings/users', 'users.index')->name('settings.users.index')->middleware('permission:user.view');
    Route::view('/settings/roles', 'roles.index')->name('settings.roles.index')->middleware('permission:role.view');
    Route::view('/settings/audit', 'settings.audit.index')->name('settings.audit.index')->middleware('permission:audit.view');

    // Administration des boulangeries — réservé au Super-Admin (cahier §19.2).
    Route::resource('settings/bakeries', BakeryController::class)
        ->except('show')
        ->names('bakeries');
});
