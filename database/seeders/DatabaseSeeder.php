<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UnitsSeeder::class,
            ProductCategorySeeder::class,
            SuperAdminSeeder::class,
            BakerySeeder::class,
            ProductSeeder::class,
            SupplierSeeder::class,
            RecipeSeeder::class,
            StockSeeder::class,
            PaymentMethodSeeder::class,
            CustomerSeeder::class,
            WholesalerSeeder::class,
        ]);
    }
}
