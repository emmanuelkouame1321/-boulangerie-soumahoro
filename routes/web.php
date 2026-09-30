<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes web
|--------------------------------------------------------------------------
| Mapping de référence : prototype_boulangerie_soumahoro/README.md
|
| Phase 1 du planning : chaque groupe de routes ci-dessous sera enveloppé
| dans le middleware `auth` puis protégé par permissions (Design System §52,
| cahier des charges §20 et §31).
*/

Route::redirect('/', '/dashboard');

Route::get('/dashboard', fn () => view('dashboard.index'))->name('dashboard');

/*
| Routes à ouvrir au fur et à mesure des phases.
| Elles sont déclarées ici pour figer les noms et éviter les routes
| « fantômes » dans la navigation.
|
| Route::get('/production', ...)->name('production.index');
| Route::get('/inventory', ...)->name('inventory.index');
| Route::get('/purchasing/orders', ...)->name('purchasing.orders.index');
| Route::get('/sales', ...)->name('sales.index');
| Route::get('/wholesalers', ...)->name('wholesalers.index');
| Route::get('/losses', ...)->name('losses.index');
| Route::get('/expenses', ...)->name('expenses.index');
| Route::get('/employees', ...)->name('employees.index');
| Route::get('/payroll', ...)->name('payroll.index');
| Route::get('/reports', ...)->name('reports.index');
| Route::get('/settings', ...)->name('settings.index');
*/
